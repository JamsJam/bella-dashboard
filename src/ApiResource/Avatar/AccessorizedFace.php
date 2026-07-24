<?php

namespace App\ApiResource\Avatar;

final readonly class AccessorizedFace
{
    public function __construct(
        public int $id,
        public string $name,
        public string $image,
    ) {
    }
}
