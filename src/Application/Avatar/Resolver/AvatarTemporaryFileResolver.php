<?php

namespace App\Application\Avatar\Resolver;

use App\Entity\AvatarTemp;
use App\Service\FileManagerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AvatarTemporaryFileResolver
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
        private FileManagerService $fileManager,
    ) {
    }

    public function resolve(AvatarTemp $avatarTemp): ?string
    {
        $path = $avatarTemp->getTempPath();

        return null === $path
            ? null
            : $this->fileManager->resolveFileWithin($path, $this->projectDir . '/var/avatar-temp');
    }
}
