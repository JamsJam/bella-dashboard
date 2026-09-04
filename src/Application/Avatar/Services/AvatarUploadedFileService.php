<?php

namespace App\Application\Avatar\Services;

use App\Service\FileManagerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AvatarUploadedFileService
{
    private string $uploadRoot;
    private string $publicRoot;

    public function __construct(
        private FileManagerService $fileManager,
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
    ) {
        $this->publicRoot = $projectDir . '/public';
        $this->uploadRoot = $projectDir . '/public/images/upload/avatar';
    }

    public function deleteFromWebPath(string $imagePath): void
    {
        $relativePath = parse_url($imagePath, PHP_URL_PATH);
        if (!is_string($relativePath) || !str_starts_with($relativePath, '/images/upload/avatar/')) {
            return;
        }

        $path = $this->publicRoot . $relativePath;
        $realPath = $this->fileManager->resolveFileWithin($path, $this->uploadRoot);
        if (null === $realPath) {
            return;
        }

        $this->fileManager->remove($realPath);

        $directory = dirname($realPath);
        while ($directory !== $this->uploadRoot && $this->fileManager->isPathWithin($directory, $this->uploadRoot)) {
            $this->fileManager->removeEmptyDirectory($directory);

            if ($this->fileManager->isDirectory($directory)) {
                break;
            }

            $directory = dirname($directory);
        }
    }
}
