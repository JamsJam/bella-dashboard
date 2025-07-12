<?php

namespace App\Registry\Avatar\Entity;

use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Eyebrows\Eyebrows;
use App\Entity\Avatar\Eyes\Eye;
use App\Entity\Avatar\Faces\Faces;
use App\Entity\Avatar\Hairs\Hairs;
use App\Entity\Avatar\Mouths\Mouths;
use App\Entity\Avatar\Noses\Nose;
use App\Enum\Avatar\BodyPartEnum;

class BodyEntityRegistry
{
    public function getEntityClass(BodyPartEnum $type): string
    {
        return match ($type) {
            BodyPartEnum::EYE => Eye::class,
            BodyPartEnum::HAIR => Hairs::class,
            BodyPartEnum::EYEBROWS => Eyebrows::class,
            BodyPartEnum::MOUTH => Mouths::class,
            BodyPartEnum::NOSE => Nose::class,
            BodyPartEnum::FACE => Faces::class,
            BodyPartEnum::BODY => Body::class,
            default => throw new \InvalidArgumentException("Invalid color entity type: $type->value"),
        };
    }
}
