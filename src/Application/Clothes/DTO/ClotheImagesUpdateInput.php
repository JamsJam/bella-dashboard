<?php

namespace App\Application\Clothes\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ClotheImagesUpdateInput
{
    /**
     * @param list<mixed>        $keptImages
     * @param list<UploadedFile> $uploadedImages
     */
    public function __construct(
        public string $slug,
        public ?int $colorId,
        public array $keptImages,
        public array $uploadedImages,
    ) {
    }
}
