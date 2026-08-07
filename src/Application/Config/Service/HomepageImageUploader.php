<?php

namespace App\Application\Config\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

final readonly class HomepageImageUploader
{
    private const PUBLIC_DIRECTORY = '/image/front';

    public function __construct(
        private SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function uploadLandingImage(UploadedFile $file): string
    {
        return $this->upload($file, 'landing');
    }

    public function uploadManualImage(UploadedFile $file, int $position): string
    {
        return $this->upload($file, sprintf('manual-%d', $position));
    }

    public function uploadReturnIcon(UploadedFile $file, int $position): string
    {
        return $this->upload($file, sprintf('return-%d', $position));
    }

    public function uploadOpenGraphImage(UploadedFile $file): string
    {
        return $this->upload($file, 'open-graph');
    }

    public function uploadCategoriesBanner(UploadedFile $file): string
    {
        return $this->upload($file, 'categories-banner');
    }

    public function uploadCategoriesOpenGraphImage(UploadedFile $file): string
    {
        return $this->upload($file, 'categories-open-graph');
    }

    private function upload(UploadedFile $file, string $fallbackName): string
    {
        $directory = $this->projectDir . '/public' . self::PUBLIC_DIRECTORY;
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Impossible de créer le dossier "%s".', $directory));
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = strtolower((string) $this->slugger->slug($originalName));
        $safeName = '' !== $safeName ? $safeName : $fallbackName;
        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        $extension = 'jpeg' === $extension ? 'jpg' : $extension;
        $filename = sprintf('%s-%s.%s', $safeName, bin2hex(random_bytes(6)), $extension);

        $file->move($directory, $filename);

        return self::PUBLIC_DIRECTORY . '/' . $filename;
    }

    public function removePreviousImage(?string $publicPath): void
    {
        if (null === $publicPath || '' === $publicPath) {
            return;
        }

        $path = parse_url($publicPath, PHP_URL_PATH);
        if (!is_string($path) || !str_starts_with($path, self::PUBLIC_DIRECTORY . '/')) {
            return;
        }

        $filename = basename($path);
        if ('' === $filename || '.' === $filename || '..' === $filename) {
            return;
        }

        $filePath = $this->projectDir . '/public' . self::PUBLIC_DIRECTORY . '/' . $filename;
        if (is_file($filePath) && !unlink($filePath)) {
            throw new \RuntimeException(sprintf('Impossible de supprimer l’ancienne image "%s".', $filename));
        }
    }
}
