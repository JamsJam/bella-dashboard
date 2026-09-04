<?php

namespace App\Application\Avatar\Mapper;

use App\Application\Avatar\Dto\AvatarColorDto;

final readonly class AvatarColorMapper
{
    public function map(object $color, string $type, int $associatedCount): AvatarColorDto
    {
        if (!method_exists($color, 'getId') || !is_int($color->getId())) {
            throw new \LogicException('An avatar color view requires a persisted color entity.');
        }

        if (!method_exists($color, 'getName') || !method_exists($color, 'getHexa')) {
            throw new \LogicException('The avatar color entity does not expose its name and hexadecimal value.');
        }

        $hexa = $color->getHexa();

        return new AvatarColorDto(
            id: $color->getId(),
            type: $type,
            name: (string) $color->getName(),
            hexa: is_string($hexa) ? $hexa : null,
            associatedCount: $associatedCount,
        );
    }
}
