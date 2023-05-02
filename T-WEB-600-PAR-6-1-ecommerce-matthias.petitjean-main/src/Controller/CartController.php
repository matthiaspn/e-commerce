<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Repository\CartItemRepository;
use App\Repository\CatalogRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/carts")
 */
class CartController extends AbstractController
{
    /**
     * @Route("/{productId}", methods={"POST"}, priority=5)
     */
    public function addProduct(Request $request, CartItemRepository $cartItemRepository, CatalogRepository $catalogRepository): JsonResponse
    {
        $productId = $request->attributes->get('productId');

        $user = $this->getUser();

        $product = $catalogRepository->find($productId);
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        $requestData = json_decode($request->getContent(), true);
        $quantity = isset($requestData['quantity']) ? intval($requestData['quantity']) : 1;
        if ($quantity <= 0) {
            return $this->json(['error' => 'Invalid quantity'], Response::HTTP_BAD_REQUEST);
        }

        // Check if the user already has the product in his cart
        $cartItem = $cartItemRepository->findOneBy(['user' => $user, 'product' => $product]);
        if ($cartItem) {
            // If yes, add the indicated quantity to the one already registered
            $cartItem->setQuantity($cartItem->getQuantity() + $quantity);
        } else {
            // If no, create a new record in the cart_item table
            $cartItem = new CartItem();
            $cartItem->setUser($user);
            $cartItem->setProduct($product);
            $cartItem->setQuantity($quantity);
        }

        $cartItemRepository->save($cartItem, true);

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/{productId}", methods={"DELETE"})
     */
    public function deleteProduct(Request $request, CartItemRepository $cartItemRepository): JsonResponse
    {
        $productId = $request->attributes->get('productId');

        if (!is_numeric($productId)) {
            return $this->json(['error' => 'Invalid product id'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();

        // Find the cart item associated with the user and the product id
        $cartItem = $cartItemRepository->findOneBy(['user' => $user, 'product' => $productId]);

        if (!$cartItem) {
            return $this->json(['error' => 'Product not found in cart'], Response::HTTP_NOT_FOUND);
        }

        $requestData = json_decode($request->getContent(), true);
        $quantity = $requestData['quantity'] ?? null;

        if ($quantity !== null) {
            if (!is_numeric($quantity) || $quantity <= 0) {
                return $this->json(['error' => 'Invalid quantity'], Response::HTTP_BAD_REQUEST);
            }

            if ($quantity > $cartItem->getQuantity()) {
                return $this->json(['error' => 'Quantity to remove is greater than quantity in cart'], Response::HTTP_BAD_REQUEST);
            }

            $cartItem->setQuantity($cartItem->getQuantity() - $quantity);

            // If the quantity of the cart item is now 0, delete it from the database
            if ($cartItem->getQuantity() <= 0) {
                $cartItemRepository->remove($cartItem, true);
            } else {
                $cartItemRepository->save($cartItem, true);
            }
        } else {
            $cartItemRepository->remove($cartItem, true);
        }

        return $this->json(['success' => true]);
    }

    /**
     * @Route("", methods={"GET"})
     */
    public function listCartItems(SerializerInterface $serializer) : JsonResponse
    {
        $user = $this->getUser();

        $cartItems = $user->getCartItems();
        if ($cartItems->isEmpty()) {
            return $this->json(['message' => 'Empty cart'], Response::HTTP_NOT_FOUND);
        }

        $cartItemsArray = $serializer->normalize($cartItems, null, [
            'attributes' => [
                'id',
                'quantity',
                'product' => [
                    'id',
                    'name',
                    'description',
                    'photo',
                    'price',
                ],
            ],
        ]);

        return $this->json($cartItemsArray);
    }

    /**
     * @Route("/validate", methods={"POST"}, priority=10)
     */
    public function validateCart(OrderService $orderService): JsonResponse
    {
        $user = $this->getUser();

        $cartItems = $user->getCartItems()->toArray();
        if (empty($cartItems)) {
            return $this->json(['error' => 'Your cart is empty.'], Response::HTTP_BAD_REQUEST);
        }

        // Créer une nouvelle commande à partir des éléments du panier
        $order = $orderService->createFromCartItems($user, $cartItems);

        return $this->json(['message' => 'Cart successfully converted to order.'], Response::HTTP_OK);
    }

}
