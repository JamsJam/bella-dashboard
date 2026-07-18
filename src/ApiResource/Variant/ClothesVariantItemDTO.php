<?php

namespace App\ApiResource\Variant;

final readonly class ClothesVariantItemDTO
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $color,
        public ?string $hexa = null,
    ) {
    }
}
