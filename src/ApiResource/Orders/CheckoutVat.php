<?php

namespace App\ApiResource\Orders;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Orders\CheckoutVatProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/checkout/vat',
            provider: CheckoutVatProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class CheckoutVat
{
    public function __construct(
        public float $vat,
    ) {
    }
}
