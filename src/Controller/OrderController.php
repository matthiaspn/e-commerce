<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/orders")
 */
// TODO: Error handling here
class OrderController extends AbstractController
{
    /**
     * @Route("", methods={"GET"})
     */
    public function getAllOrders(SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();
        $orders = $user->getOrders();
        $ordersArray = $this->normalizeOrders($serializer, $orders);
        return $this->json($ordersArray);
    }

    /**
     * @Route("/{orderId}", methods={"GET"})
     */
    public function getOrder(int $orderId, SerializerInterface $serializer, OrderRepository $orderRepository): JsonResponse
    {
        $user = $this->getUser();

        $order = $orderRepository->findOneBy(['id' => $orderId, 'user' => $user]);
        if (!$order) {
            return $this->json(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $orderArray = $this->normalizeOrders($serializer, [$order]);

        return $this->json($orderArray[0]);
    }

    private function normalizeOrders(SerializerInterface $serializer, iterable $orders): array
    {
        $context = [
            'attributes' => [
                'id',
                'totalPrice',
                'creationDate',
            ],
            'associations' => [
                'orderItems' => [
                    'attributes' => [],
                    'associations' => [
                        'productId' => [
                            'attributes' => [
                                'id',
                                'name',
                                'description',
                                'photo',
                                'price',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $ordersArray = [];
        foreach ($orders as $order) {
            $orderArray = $serializer->normalize($order, null, $context);
            $products = [];

            foreach ($order->getOrderItems() as $orderItem) {
                $products[] = $orderItem->getProductId();
            }

            $orderArray['products'] = $serializer->normalize($products, null, $context['associations']['orderItems']['associations']['productId']);
            unset($orderArray['orderItems']);

            $ordersArray[] = $orderArray;
        }

        return $ordersArray;
    }
}
