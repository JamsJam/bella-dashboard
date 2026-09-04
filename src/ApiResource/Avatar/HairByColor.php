<?php

namespace App\ApiResource\Avatar;

final readonly class HairByColor
{
    /** @param list<string> $images */
    public function __construct(
        public int $id,
        public string $name,
        public array $images,
    ) {
    }
}
