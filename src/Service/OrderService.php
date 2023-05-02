<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function createFromCartItems(User $user, iterable $cartItems): Order
    {
        $order = new Order();
        $order->setCreationDate(new \DateTime());

        $totalPrice = 0;
        foreach ($cartItems as $cartItem) {
            $product = $cartItem->getProduct();
            $quantity = $cartItem->getQuantity();
            $price = $product->getPrice() * $quantity;

            $orderItem = new OrderItem();
            $orderItem->setOrderId($order);
            $orderItem->setProductId($product);
            $orderItem->setQuantity($quantity);

            $this->entityManager->persist($orderItem);

            $totalPrice += $price;

            $this->entityManager->remove($cartItem);
        }

        $order->setUser($user);
        $order->setTotalPrice($totalPrice);
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $order;
    }
}
