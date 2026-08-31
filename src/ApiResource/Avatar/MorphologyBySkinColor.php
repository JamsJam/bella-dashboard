<?php

namespace App\ApiResource\Avatar;

final readonly class MorphologyBySkinColor
{
    public function __construct(
        public int $id,
        public string $name,
        public string $image,
    ) {
    }
}
