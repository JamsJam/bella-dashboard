<?php

namespace App\ApiResource\Variant;

final readonly class RelatedVariantDTO
{
    /** @param list<string> $images */
    public function __construct(
        public string $name,
        public string $slug,
        public ?string $image = null,
        public array $images = [],
    ) {
    }
}
