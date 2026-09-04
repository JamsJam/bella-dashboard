<?php

namespace App\Application\Avatar\Dto;

final readonly class AvatarColorDto
{
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public ?string $hexa,
        public int $associatedCount,
    ) {
    }
}
