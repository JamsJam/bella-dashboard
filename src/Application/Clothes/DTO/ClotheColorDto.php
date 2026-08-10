<?php

namespace App\Application\Clothes\DTO;

final readonly class ClotheColorDto
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $hexa,
        public int $clothesCount,
        public int $variantsCount,
    ) {
    }
}
