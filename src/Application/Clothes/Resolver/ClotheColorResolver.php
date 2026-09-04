<?php

namespace App\Application\Clothes\Resolver;

use App\Application\Clothes\DTO\VariantGroupInput;
use App\Application\Clothes\Factory\ClotheColorFactory;
use App\Application\Clothes\Provider\ClotheColorProvider;
use App\Entity\Clothes\Clothescolor;

final readonly class ClotheColorResolver
{
    public function __construct(
        private ClotheColorProvider $provider,
        private ClotheColorFactory $factory,
    ) {
    }

    public function resolve(VariantGroupInput $input): Clothescolor
    {
        if ($input->color instanceof Clothescolor) {
            return $input->color;
        }

        $name = trim((string) $input->newColorName);
        $existingColor = $this->provider->findOneByName($name);

        if ($existingColor instanceof Clothescolor) {
            return $existingColor;
        }

        return $this->factory->create(
            $name,
            (string) $input->newColorHex,
        );
    }
}
