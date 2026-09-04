<?php

namespace App\Application\Avatar\Dto;

final readonly class AvatarColorModalDto
{
    /**
     * @param list<AvatarColorDto>    $colors
     * @param list<AvatarColorTabDto> $tabs
     */
    public function __construct(
        public string $activeType,
        public string $activeLabel,
        public array $colors,
        public array $tabs,
    ) {
    }
}
