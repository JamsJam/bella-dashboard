<?php

namespace App\Application\Orders\Services;

use App\ApiResource\Orders\CheckoutCartInput;
use App\Entity\Orders\Cart;
use App\Entity\Orders\CartItem;
use App\Entity\Users\Customers;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CheckoutCartService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function createPendingCart(CheckoutCartInput $input, Customers $customer): Cart
    {
        $cart = (new Cart())
            ->setCustomer($customer)
            ->setCurrency($input->currency)
            ->setStatus(Cart::STATUS_PENDING);

        foreach ($input->items as $itemPayload) {
            $item = $this->createItem($itemPayload);
            $cart->addItem($item);
        }

        if ($cart->getItems()->isEmpty()) {
            throw new \InvalidArgumentException('Cart must contain at least one item.');
        }

        $cart->recalculateTotals();

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        return $cart;
    }

    private function createItem(array $payload): CartItem
    {
        foreach (['productId', 'name', 'quantity', 'unitPriceTTC'] as $field) {
            if (!array_key_exists($field, $payload)) {
                throw new \InvalidArgumentException(sprintf('Missing cart item field "%s".', $field));
            }
        }

        $productId = filter_var($payload['productId'], FILTER_VALIDATE_INT);
        $quantity = filter_var($payload['quantity'], FILTER_VALIDATE_INT);
        $unitPriceTTC = filter_var($payload['unitPriceTTC'], FILTER_VALIDATE_INT);
        $name = trim((string) $payload['name']);

        if ($productId === false || $productId <= 0 || $quantity === false || $quantity <= 0 || $unitPriceTTC === false || $unitPriceTTC <= 0 || $name === '') {
            throw new \InvalidArgumentException('Invalid cart item payload.');
        }

        return (new CartItem())
            ->setProductId($productId)
            ->setName($name)
            ->setQuantity($quantity)
            ->setUnitPriceTTC($unitPriceTTC);
    }
}
