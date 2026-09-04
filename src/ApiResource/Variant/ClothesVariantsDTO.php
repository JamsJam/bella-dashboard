<?php

namespace App\ApiResource\Variant;

final readonly class ClothesVariantsDTO
{
    /** @param list<ClothesVariantItemDTO> $variants */
    public function __construct(
        public string $name,
        public array $variants = [],
    ) {
    }
}
