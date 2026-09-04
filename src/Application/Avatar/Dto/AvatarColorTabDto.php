<?php

namespace App\Application\Avatar\Dto;

final readonly class AvatarColorTabDto
{
    public function __construct(
        public string $type,
        public string $label,
        public bool $active,
    ) {
    }
}
