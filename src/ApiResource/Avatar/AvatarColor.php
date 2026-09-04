<?php

namespace App\ApiResource\Avatar;

final readonly class AvatarColor
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $hexa,
    ) {
    }
}
