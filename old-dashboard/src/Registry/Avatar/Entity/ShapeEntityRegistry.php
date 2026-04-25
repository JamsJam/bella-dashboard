<?php

namespace App\Registry\Avatar\Entity;

use App\Entity\Avatar\Eyebrows\Eyebrowshape;
use App\Entity\Avatar\Eyes\Eyeshape;
use App\Entity\Avatar\Faces\Faceshape;
use App\Entity\Avatar\Hairs\Hairshape;
use App\Entity\Avatar\Mouths\Mouthshape;
use App\Entity\Avatar\Noses\Noseshape;
use App\Enum\Avatar\BodyPartEnum;

class ShapeEntityRegistry
{
    /**
     * Retourn l'entity correspondant au type.
     */
    public function getEntityClass(BodyPartEnum $type): string
    {
        return match ($type) {
            BodyPartEnum::EYE => Eyeshape::class,
            BodyPartEnum::HAIR => Hairshape::class,
            BodyPartEnum::EYEBROWS => Eyebrowshape::class,
            BodyPartEnum::MOUTH => Mouthshape::class,
            BodyPartEnum::FACE => Faceshape::class,
            BodyPartEnum::NOSE => Noseshape::class,
            default => throw new \InvalidArgumentException("Invalid shape entity type: $type->value"),
        };
    }
}
