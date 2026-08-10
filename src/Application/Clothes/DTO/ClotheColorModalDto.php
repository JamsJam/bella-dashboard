<?php

namespace App\Application\Clothes\DTO;

final readonly class ClotheColorModalDto
{
    /** @param list<ClotheColorDto> $colors */
    public function __construct(
        public array $colors,
    ) {
    }
}
