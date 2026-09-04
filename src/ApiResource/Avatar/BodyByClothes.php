<?php

namespace App\ApiResource\Avatar;

final readonly class BodyByClothes
{
    public function __construct(
        public int $bodyId,
        public string $bodyName,
        public string $image,
        public int $skinColorId,
        public string $skinColor,
        public int $morphologyId,
        public string $morphology,
        public int $morphotypeId,
        public string $morphotype,
        public int $sizeId,
        public string $size,
        public ?int $clothesId,
        public ?string $clothesSlug,
    ) {
    }
}
