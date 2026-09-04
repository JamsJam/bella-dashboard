<?php

namespace App\ApiResource\Variant;

final readonly class SizeGuideDTO
{
    /** @param list<SizeGuideSizeDTO> $sizes */
    public function __construct(
        public string $unit,
        public array $sizes = [],
    ) {
    }
}
