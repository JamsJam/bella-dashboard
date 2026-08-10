<?php

namespace App\Application\Avatar\Services;

use App\Entity\AvatarTemp;
use App\Service\FileManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class AvatarPartRenameQueueService
{
    public function __construct(
        private AvatarResolverService $avatarResolverService,
        private AvatarImageUploadValidator $avatarImageUploadValidator,
        private EntityManagerInterface $entityManager,
        private FileManagerService $fileManager,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function queueForRename(string $part, int $id): AvatarTemp
    {
        $entityClass = $this->avatarResolverService->resolveEntity($part);
        $avatarPart = $this->entityManager->find($entityClass, $id);

        if (!is_object($avatarPart)) {
            throw new \RuntimeException('Avatar part not found.', 404);
        }

        $sourcePath = $this->resolveSourcePath($avatarPart);
        if (null === $sourcePath || !$this->fileManager->isFile($sourcePath)) {
            throw new \RuntimeException('Avatar PNG file not found.', 422);
        }

        if (!$this->avatarImageUploadValidator->isValidPngFile($sourcePath)) {
            throw new \RuntimeException('Avatar file is not a valid PNG.', 422);
        }

        $fileId = bin2hex(random_bytes(16));
        $uploadDir = $this->projectDir . '/var/avatar-temp/' . $fileId;

        try {
            $this->fileManager->ensureDirectory($uploadDir);
        } catch (\Throwable) {
            throw new \RuntimeException('Unable to create avatar temporary directory.', 500);
        }

        $originalName = basename($sourcePath);
        $storedName = $this->createStoredImageFilename($originalName);
        $tempPath = $uploadDir . '/' . $storedName;

        $this->copySourceFile($sourcePath, $tempPath);

        $sourceChecksum = hash_file('sha256', $sourcePath);
        $temporaryChecksum = hash_file('sha256', $tempPath);
        if (false === $sourceChecksum || false === $temporaryChecksum || !hash_equals($sourceChecksum, $temporaryChecksum)) {
            $this->fileManager->remove([$tempPath, $uploadDir]);

            throw new \RuntimeException('Avatar temporary copy checksum mismatch.', 500);
        }

        $avatarTemp = (new AvatarTemp())
            ->setOriginalName($originalName)
            ->setStoredName($storedName)
            ->setRelativePath($part . '/' . $originalName)
            ->setTempPath($tempPath)
            ->setMimeType('image/png')
            ->setFileSize((int) filesize($tempPath))
            ->setExtension('png')
            ->setStatus('uploaded');

        $this->entityManager->persist($avatarTemp);
        $this->entityManager->flush();

        try {
            $this->fileManager->remove($sourcePath);
        } catch (\Throwable) {
            $this->entityManager->remove($avatarTemp);
            $this->entityManager->flush();
            $this->fileManager->remove([$tempPath, $uploadDir]);

            throw new \RuntimeException('Unable to remove original avatar file after queuing its verified copy.', 500);
        }

        try {
            $this->entityManager->remove($avatarPart);
            $this->entityManager->flush();
        } catch (\Throwable $exception) {
            // The verified temporary copy allows restoring the original if Doctrine fails.
            $this->fileManager->copy($tempPath, $sourcePath, true);
            $this->entityManager->remove($avatarTemp);
            $this->entityManager->flush();
            $this->fileManager->remove([$tempPath, $uploadDir]);
            throw $exception;
        }

        $this->fileManager->removeEmptyDirectory(dirname($sourcePath));

        return $avatarTemp;
    }

    private function resolveSourcePath(object $avatarPart): ?string
    {
        $image = null;

        if (method_exists($avatarPart, 'getImage')) {
            $image = $avatarPart->getImage();
        } elseif (method_exists($avatarPart, 'getImages')) {
            $images = $avatarPart->getImages();
            $image = is_array($images) ? ($images[0] ?? $images['front'] ?? reset($images) ?: null) : null;
        }

        if (!is_string($image) || '' === trim($image)) {
            return null;
        }

        if (str_starts_with($image, '/') && $this->fileManager->isFile($image)) {
            return $image;
        }

        $relativePath = ltrim($image, '/');
        foreach (
            [
                $this->projectDir . '/public/' . $relativePath,
                $this->projectDir . '/' . $relativePath,
            ] as $path
        ) {
            if ($this->fileManager->isFile($path)) {
                return $path;
            }
        }

        return null;
    }

    private function copySourceFile(string $sourcePath, string $tempPath): void
    {
        try {
            $this->fileManager->copy($sourcePath, $tempPath);
        } catch (\Throwable) {
            throw new \RuntimeException('Unable to copy avatar file to rename queue.', 500);
        }
    }

    private function createStoredImageFilename(string $originalName): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $slugger = new AsciiSlugger();
        $safeName = strtolower((string) $slugger->slug($baseName));

        if ('' === $safeName) {
            $safeName = 'avatar';
        }

        return $safeName . '-' . bin2hex(random_bytes(4)) . '.png';
    }
}
