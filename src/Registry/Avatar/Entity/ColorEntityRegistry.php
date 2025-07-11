<?php

namespace App\Registry\Avatar\Entity;

use App\Entity\Avatar\Eyebrows\Eyebrowscolor;
use App\Entity\Avatar\Eyes\Eyecolor;
use App\Entity\Avatar\Hairs\Hairscolor;
use App\Entity\Avatar\Mouths\Mouthscolor;
use App\Entity\Avatar\Skincolor;
use App\Enum\Avatar\BodyPartEnum;

class ColorEntityRegistry
{
    public function getEntityClass(BodyPartEnum $type): string
    {
        return match ($type) {
            BodyPartEnum::EYE => Eyecolor::class,
            BodyPartEnum::HAIR => Hairscolor::class,
            BodyPartEnum::SKIN => Skincolor::class,
            BodyPartEnum::EYEBROWS => Eyebrowscolor::class,
            BodyPartEnum::MOUTH => Mouthscolor::class,
            default => throw new \InvalidArgumentException("Invalid color entity type: $type->value"),
        };
    }
}
