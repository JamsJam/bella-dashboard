<?php

namespace App\ApiResource\Orders;

final readonly class CustomerOrderItem
{
    public function __construct(
        public int $productId,
        public string $name,
        public int $quantity,
        public int $unitPriceTTC,
        public int $totalTTC,
    ) {
    }
}
