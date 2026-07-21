<?php

namespace App\ApiResource\Avatar;

final readonly class MorphotypeBySkinColor
{
    public function __construct(
        public int $id,
        public string $name,
        public int $sizeId,
        public string $size,
    ) {
    }
}
