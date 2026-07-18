<?php

namespace App\ApiResource\Variant;

final readonly class VariantColorDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $hexa = null,
    ) {
    }
}
