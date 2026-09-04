<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\DTO\ClotheImageInput;
use App\Service\FileManagerService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class ClotheImageStorageService
{
    private const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg'];
    private const IMAGE_MIME_TYPES = ['image/png', 'image/jpeg'];

    public function __construct(
        private FileManagerService $fileManager,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @param list<UploadedFile> $files
     *
     * @return list<string>
     */
    public function storeVariantImages(
        array $files,
        string $clotheName,
        string $colorName,
    ): array {
        return array_map(
            static fn (ClotheImageInput $image): string => $image->path,
            $this->storeClotheImages($files, $clotheName, $colorName),
        );
    }

    /**
     * @param list<UploadedFile> $files
     *
     * @return list<ClotheImageInput>
     */
    public function storeClotheImages(
        array $files,
        string $clotheName,
        ?string $colorName = null,
    ): array {
        $slugger = new AsciiSlugger();
        $directoryLabel = null === $colorName
            ? $clotheName
            : $clotheName.'-'.$colorName;
        $directoryName = strtolower((string) $slugger->slug($directoryLabel));
        $directory = sprintf(
            '%s/public/images/upload/clothes/%s',
            $this->projectDir,
            $directoryName,
        );
        $this->fileManager->ensureDirectory($directory);
        $paths = [];

        foreach (array_values($files) as $position => $file) {
            if (!$file instanceof UploadedFile || !$this->isValidImage($file)) {
                continue;
            }

            $extension = strtolower((string) $file->guessExtension());

            if ('jpeg' === $extension) {
                $extension = 'jpg';
            }

            if (
                '' === $extension
                || !in_array($extension, self::IMAGE_EXTENSIONS, true)
            ) {
                $extension = strtolower(
                    (string) $file->getClientOriginalExtension(),
                );
            }

            $filename = sprintf(
                '%02d-%s.%s',
                $position + 1,
                bin2hex(random_bytes(5)),
                $extension,
            );
            $file->move($directory, $filename);
            $paths[] = new ClotheImageInput(
                path: sprintf(
                    '/images/upload/clothes/%s/%s',
                    $directoryName,
                    $filename,
                ),
                originalName: (string) $file->getClientOriginalName(),
                position: $position,
            );
        }

        return $paths;
    }

    private function isValidImage(UploadedFile $image): bool
    {
        return in_array(
            strtolower((string) $image->getClientOriginalExtension()),
            self::IMAGE_EXTENSIONS,
            true,
        ) && in_array(
            (string) $image->getMimeType(),
            self::IMAGE_MIME_TYPES,
            true,
        );
    }
}
