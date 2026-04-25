<?php

namespace App\Registry\Avatar\Repository;

use App\Enum\Avatar\BodyPartEnum;
use App\Repository\Avatar\Eyebrows\EyebrowshapeRepository;
use App\Repository\Avatar\Eyes\EyeshapeRepository;
use App\Repository\Avatar\Faces\FaceshapeRepository;
use App\Repository\Avatar\Hairs\HairshapeRepository;
use App\Repository\Avatar\Mouths\MouthshapeRepository;
use App\Repository\Avatar\Noses\NoseshapeRepository;

class ShapeRepositoryRegistry
{
    public function getRepositoryClass(BodyPartEnum $type): string
    {
        return match ($type) {
            BodyPartEnum::EYE => EyeshapeRepository::class,
            BodyPartEnum::EYEBROWS => EyebrowshapeRepository::class,
            BodyPartEnum::HAIR => HairshapeRepository::class,
            BodyPartEnum::MOUTH => MouthshapeRepository::class,
            BodyPartEnum::FACE => FaceshapeRepository::class,
            BodyPartEnum::NOSE => NoseshapeRepository::class,
            default => throw new \InvalidArgumentException("Invalid color entity type: $type->value"),
        };
    }
}
