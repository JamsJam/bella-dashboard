<?php

namespace App\Registry\Avatar\Repository;

use App\Enum\Avatar\BodyPartEnum;
use App\Repository\Avatar\Eyebrows\EyebrowscolorRepository;
use App\Repository\Avatar\Eyes\EyecolorRepository;
use App\Repository\Avatar\Hairs\HairscolorRepository;
use App\Repository\Avatar\Mouths\MouthscolorRepository;
use App\Repository\Avatar\SkincolorRepository;

class ColorRepositoryRegistry
{
    public function getRepositoryClass(BodyPartEnum $type): string
    {
        return match ($type) {
            BodyPartEnum::EYE => EyecolorRepository::class,
            BodyPartEnum::EYEBROWS => EyebrowscolorRepository::class,
            BodyPartEnum::HAIR => HairscolorRepository::class,
            BodyPartEnum::MOUTH => MouthscolorRepository::class,
            BodyPartEnum::SKIN => SkincolorRepository::class,
            default => throw new \InvalidArgumentException("Invalid color entity type: $type->value"),
        };
    }
}
