<?php

namespace App\Service\SymfonyUX;

use Symfony\UX\Cropperjs\Model\Crop;

final readonly class CropperResult
{
    public function __construct(
        public string $serverPath,
        public string $publicUrl,
        public Crop $crop,
        public ?string $temporaryFilename = null,
    ) {
    }
}
