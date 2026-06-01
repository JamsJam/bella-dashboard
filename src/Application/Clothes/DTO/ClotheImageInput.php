<?php

namespace App\Application\Clothes\DTO;

final readonly class ClotheImageInput
{
    public function __construct(
        public string $path,
        public string $originalName,
        public int $position,
    ) {
    }
}
