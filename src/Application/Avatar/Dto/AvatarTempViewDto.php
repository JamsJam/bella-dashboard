<?php

namespace App\Application\Avatar\Dto;

final readonly class AvatarTempViewDto
{
    public function __construct(
        public int $id,
        public ?string $originalName,
        public ?string $storedName,
        public string $status,
        public ?string $finalName,
    ) {
    }
}
