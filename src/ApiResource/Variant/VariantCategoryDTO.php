<?php

namespace App\ApiResource\Variant;

final readonly class VariantCategoryDTO
{
    public function __construct(
        public string $name,
        public string $slug,
    ) {
    }
}
