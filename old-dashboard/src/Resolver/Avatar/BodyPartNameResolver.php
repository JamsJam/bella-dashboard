<?php

namespace App\Resolver\Avatar;

use App\Enum\Avatar\BodyPartEnum;
use App\Registry\Avatar\FilePath\AvatarFilePathRegistry;
use App\Validator\Avatar\Filename\BodyFilenameValidator;
use App\Validator\Avatar\Filename\HairFilenameValidator;
use App\Validator\Avatar\Filename\PartFilenameValidator;

final class BodyPartNameResolver
{
    public function __construct(
        private BodyFilenameValidator $body_filename_validator,
        private HairFilenameValidator $hair_filename_validator,
        private PartFilenameValidator $part_filename_validator,
        private AvatarFilePathRegistry $pathregistery,
    ) {
    }

    public function getFilePath($name)
    {
        $key = BodyPartEnum::from(explode('__', $name)[0]);
        $this->resolveValidation($name, $key);

        return $this->resoleFilePath($name, $key);
    }

    private function resolveValidation($name, $key)
    {
        match ($key) {
            BodyPartEnum::HAIR => $this->hair_filename_validator->validate($name),
            BodyPartEnum::BODY => $this->body_filename_validator->validate($name),
            BodyPartEnum::EYE,
            BodyPartEnum::EYEBROWS,
            BodyPartEnum::MOUTH,
            BodyPartEnum::NOSE,
            BodyPartEnum::FACE => $this->part_filename_validator->validate($name),
        };
    }

    private function resoleFilePath($name, $key): string
    {
        return match ($key) {
            BodyPartEnum::HAIR => $this->pathregistery->getHairFilePathDirectory($name),
            BodyPartEnum::BODY => $this->pathregistery->getBodyFilePathDirectory($name),
            BodyPartEnum::EYE,
            BodyPartEnum::EYEBROWS,
            BodyPartEnum::MOUTH,
            BodyPartEnum::NOSE,
            BodyPartEnum::FACE => $this->pathregistery->getPartFilePathDirectory($name),
        };
    }
}
