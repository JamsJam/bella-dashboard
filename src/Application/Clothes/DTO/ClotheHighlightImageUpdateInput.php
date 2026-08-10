<?php

namespace App\Application\Clothes\DTO;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ClotheHighlightImageUpdateInput
{
    public function __construct(
        public string $slug,
        public string $slot,
        public string $selectedImage,
        public ?UploadedFile $uploadedImage,
    ) {
    }
}
