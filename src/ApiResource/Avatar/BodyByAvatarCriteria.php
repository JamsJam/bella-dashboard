<?php

namespace App\ApiResource\Avatar;

final readonly class BodyByAvatarCriteria
{
    public function __construct(
        public int $id,
        public string $name,
        public string $image,
    ) {
    }
}
