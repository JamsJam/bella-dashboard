<?php

namespace App\ApiResource\Variant;

final readonly class SizeGuideSizeDTO
{
    /** @param list<SizeGuideMeasurementDTO> $measurements */
    public function __construct(
        public string $label,
        public array $measurements = [],
    ) {
    }
}
