<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

final readonly class FileManagerService
{
    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->exists($path);
    }

    public function isFile(string $path): bool
    {
        return $this->filesystem->exists($path) && is_file($path);
    }

    public function isDirectory(string $path): bool
    {
        return $this->filesystem->exists($path) && is_dir($path);
    }

    public function ensureDirectory(string $directory, int $mode = 0775): void
    {
        $this->filesystem->mkdir($directory, $mode);
    }

    public function copy(string $source, string $destination, bool $overwrite = false): void
    {
        $this->filesystem->copy($source, $destination, $overwrite);
    }

    public function move(string $source, string $destination, bool $overwrite = false): void
    {
        $this->filesystem->rename($source, $destination, $overwrite);
    }

    public function remove(string|iterable $paths): void
    {
        $this->filesystem->remove($paths);
    }

    public function removeEmptyDirectory(string $directory): void
    {
        if ($this->isDirectory($directory)) {
            @rmdir($directory);
        }
    }

    public function resolveFileWithin(string $path, string $allowedRoot): ?string
    {
        if (!$this->isFile($path) || !$this->isPathWithin($path, $allowedRoot)) {
            return null;
        }

        return realpath($path) ?: null;
    }

    public function isPathWithin(string $path, string $allowedRoot): bool
    {
        $realPath = realpath($path);
        $realRoot = realpath($allowedRoot);

        return false !== $realPath
            && false !== $realRoot
            && Path::isBasePath($realRoot, $realPath);
    }
}
