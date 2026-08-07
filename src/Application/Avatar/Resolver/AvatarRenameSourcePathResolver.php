<?php

namespace App\Application\Avatar\Resolver;

use App\Application\Avatar\Services\AvatarImageUploadValidator;
use App\Entity\AvatarTemp;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AvatarRenameSourcePathResolver
{
    public function __construct(
        private AvatarImageUploadValidator $avatarImageUploadValidator,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function resolve(AvatarTemp $avatarTemp): string
    {
        $sourcePath = $avatarTemp->getTempPath();

        if (null === $sourcePath || !is_file($sourcePath)) {
            throw new \RuntimeException('Temporary avatar file not found.');
        }

        $this->assertPathIsInside($sourcePath, $this->projectDir . '/var/avatar-temp');

        if (!$this->avatarImageUploadValidator->isValidPngFile($sourcePath)) {
            throw new \RuntimeException('Temporary avatar file is not a valid PNG.');
        }

        return $sourcePath;
    }

    private function assertPathIsInside(string $path, string $allowedRoot): void
    {
        $allowedRoot = rtrim($allowedRoot, '/') . '/';
        $directory = is_dir($path) ? $path : dirname($path);

        $realAllowedRoot = realpath($allowedRoot);
        $realDirectory = realpath($directory);

        if (false === $realAllowedRoot || false === $realDirectory || !str_starts_with($realDirectory . '/', rtrim($realAllowedRoot, '/') . '/')) {
            throw new \RuntimeException('Path is outside the allowed avatar directory.');
        }
    }
}
