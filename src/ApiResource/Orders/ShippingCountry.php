<?php

namespace App\ApiResource\Orders;

final readonly class ShippingCountry
{
    public function __construct(
        public string $destination,
        public int $priceCents,
        public ?string $flag,
    ) {
    }
}
