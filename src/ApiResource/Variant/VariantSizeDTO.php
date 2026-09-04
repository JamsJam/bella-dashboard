<?php

namespace App\ApiResource\Variant;

final readonly class VariantSizeDTO
{
    public function __construct(
        public int $id,
        public string $name,
    ) {
    }
}
