<?php

namespace App\Application\Clothes\Mapper;

use App\Application\Clothes\DTO\ClotheColorDto;
use App\Entity\Clothes\Clothescolor;

final readonly class ClotheColorMapper
{
    public function map(Clothescolor $color): ClotheColorDto
    {
        $id = $color->getId();
        if (null === $id) {
            throw new \LogicException('A clothes color view requires a persisted entity.');
        }

        return new ClotheColorDto(
            id: $id,
            name: (string) $color->getName(),
            hexa: $color->getHexa(),
            clothesCount: $color->getClothes()->count(),
            variantsCount: $color->getVariants()->count(),
        );
    }
}
