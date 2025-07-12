<?php

namespace App\Registry\Avatar\Repository;

use App\Enum\Avatar\BodyPartEnum;
use App\Repository\Avatar\Body\BodyRepository;
use App\Repository\Avatar\Eyebrows\EyebrowsRepository;
use App\Repository\Avatar\Eyes\EyeRepository;
use App\Repository\Avatar\Faces\FacesRepository;
use App\Repository\Avatar\Hairs\HairsRepository;
use App\Repository\Avatar\Mouths\MouthsRepository;
use App\Repository\Avatar\Noses\NoseRepository;

class BodyRepositoryRegistry
{
    public function getRepositoryClass(BodyPartEnum $type): string
    {
        return match ($type) {
            BodyPartEnum::EYE => EyeRepository::class,
            BodyPartEnum::HAIR => HairsRepository::class,
            BodyPartEnum::EYEBROWS => EyebrowsRepository::class,
            BodyPartEnum::MOUTH => MouthsRepository::class,
            BodyPartEnum::NOSE => NoseRepository::class,
            BodyPartEnum::FACE => FacesRepository::class,
            BodyPartEnum::BODY => BodyRepository::class,
            default => throw new \InvalidArgumentException("Invalid color entity type: $type->value"),
        };
    }
}
