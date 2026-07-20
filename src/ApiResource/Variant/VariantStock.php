<?php

namespace App\ApiResource\Variant;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Variant\VariantStockProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/variants/{id}/stock',
            requirements: ['id' => '\\d+'],
            provider: VariantStockProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class VariantStock
{
    public function __construct(
        public int $variantId,
        public int $stock,
        public bool $available,
    ) {
    }
}
