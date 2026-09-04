<?php

namespace App\ApiResource\Variant;

final readonly class SizeGuideMeasurementDTO
{
    public function __construct(
        public string $uuid,
        public string $label,
        public string $value,
        public string $unit,
    ) {
    }
}
