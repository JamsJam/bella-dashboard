<?php

namespace App\Application\Avatar\Resolver;

use App\Application\Avatar\Services\AvatarImageUploadValidator;
use App\Entity\AvatarTemp;

final readonly class AvatarRenameSourcePathResolver
{
    public function __construct(
        private AvatarImageUploadValidator $avatarImageUploadValidator,
        private AvatarTemporaryFileResolver $temporaryFileResolver,
    ) {
    }

    public function resolve(AvatarTemp $avatarTemp): string
    {
        $sourcePath = $this->temporaryFileResolver->resolve($avatarTemp);
        if (null === $sourcePath) {
            throw new \RuntimeException('Temporary avatar file not found.');
        }

        if (!$this->avatarImageUploadValidator->isValidPngFile($sourcePath)) {
            throw new \RuntimeException('Temporary avatar file is not a valid PNG.');
        }

        return $sourcePath;
    }
}
