<?php

namespace App\Service\SymfonyUX;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Model\Crop;

final readonly class CropperService
{
    private const CROPPABLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const TEMPORARY_PUBLIC_DIRECTORY = '/images/upload/tmp/cropper';

    public function __construct(
        private CropperInterface $cropper,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function prepareUploadedFile(UploadedFile $file, string $prefix = 'crop'): CropperResult
    {
        $extension = $this->resolveExtension($file);
        $filename = sprintf('%s-%s.%s', $this->sanitizePrefix($prefix), bin2hex(random_bytes(8)), $extension);
        $directory = $this->serverPath(self::TEMPORARY_PUBLIC_DIRECTORY);

        $this->createDirectory($directory);
        $file->move($directory, $filename);

        $publicUrl = self::TEMPORARY_PUBLIC_DIRECTORY.'/'.$filename;

        try {
            return $this->createResult($publicUrl, $filename);
        } catch (\Throwable $exception) {
            @unlink($directory.'/'.$filename);

            throw $exception;
        }
    }

    public function createFromPublicUrl(?string $publicUrl): ?CropperResult
    {
        if ($publicUrl === null || $publicUrl === '' || !$this->isCroppable($publicUrl)) {
            return null;
        }

        try {
            $temporaryFilename = str_starts_with($publicUrl, self::TEMPORARY_PUBLIC_DIRECTORY.'/')
                ? basename($publicUrl)
                : null;

            return $this->createResult($publicUrl, $temporaryFilename);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    public function removeTemporaryFile(?CropperResult $image): void
    {
        if ($image?->temporaryFilename === null) {
            return;
        }

        $temporaryDirectory = realpath($this->serverPath(self::TEMPORARY_PUBLIC_DIRECTORY));
        $serverPath = realpath($image->serverPath);

        if ($temporaryDirectory !== false && $serverPath !== false && str_starts_with($serverPath, $temporaryDirectory.'/')) {
            @unlink($serverPath);
        }
    }

    public function saveCrop(
        Crop $crop,
        string $publicDirectory,
        string $prefix,
        string $format = 'png',
        int $quality = 90,
    ): string {
        $extension = strtolower($format);
        if (!in_array($extension, self::CROPPABLE_EXTENSIONS, true)) {
            throw new \InvalidArgumentException(sprintf('Unsupported crop format "%s".', $format));
        }

        $directory = $this->serverPath($publicDirectory);
        $this->createDirectory($directory);

        $filename = sprintf('%s-crop-%s.%s', $this->sanitizePrefix($prefix), bin2hex(random_bytes(4)), $extension);
        $serverPath = $directory.'/'.$filename;

        if (file_put_contents($serverPath, $crop->getCroppedImage($format, $quality, true)) === false) {
            throw new \RuntimeException(sprintf('Unable to write cropped image "%s".', $serverPath));
        }

        return rtrim($publicDirectory, '/').'/'.$filename;
    }

    private function createResult(string $publicUrl, ?string $temporaryFilename = null): CropperResult
    {
        $serverPath = $this->serverPath($publicUrl, true);
        $crop = $this->cropper->createCrop($serverPath);

        return new CropperResult($serverPath, $publicUrl, $crop, $temporaryFilename);
    }

    private function resolveExtension(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if (!in_array($extension, self::CROPPABLE_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Only JPEG, PNG and WebP images can be cropped.');
        }

        return $extension;
    }

    private function isCroppable(string $publicUrl): bool
    {
        $path = parse_url($publicUrl, PHP_URL_PATH);
        $extension = strtolower(pathinfo(is_string($path) ? $path : $publicUrl, PATHINFO_EXTENSION));

        return in_array($extension, self::CROPPABLE_EXTENSIONS, true);
    }

    private function serverPath(string $publicPath, bool $mustExist = false): string
    {
        $path = parse_url($publicPath, PHP_URL_PATH);
        $relativePath = ltrim(is_string($path) ? $path : $publicPath, '/');
        if ($relativePath === '' || str_contains($relativePath, "\0")) {
            throw new \InvalidArgumentException('Invalid public image path.');
        }

        $publicDirectory = $this->projectDir.'/public';
        $serverPath = $publicDirectory.'/'.$relativePath;

        if ($mustExist) {
            $realPublicDirectory = realpath($publicDirectory);
            $realServerPath = realpath($serverPath);
            if ($realPublicDirectory === false || $realServerPath === false || !str_starts_with($realServerPath, $realPublicDirectory.'/')) {
                throw new \InvalidArgumentException('The public image does not exist.');
            }

            return $realServerPath;
        }

        if (str_contains('/'.$relativePath.'/', '/../')) {
            throw new \InvalidArgumentException('Invalid public image path.');
        }

        return $serverPath;
    }

    private function createDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create image directory "%s".', $directory));
        }
    }

    private function sanitizePrefix(string $prefix): string
    {
        $prefix = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]+/', '-', $prefix));

        return trim($prefix, '-') ?: 'crop';
    }
}
