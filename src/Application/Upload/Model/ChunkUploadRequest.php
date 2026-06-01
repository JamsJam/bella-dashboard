<?php

namespace App\Application\Upload\Model;

use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ChunkUploadRequest
{
    public function __construct(
        public UploadedFile $chunk,
        public string $fileId,
        public string $originalName,
        public string $relativePath,
        public int $chunkIndex,
        public int $totalChunks,
        public int $fileSize,
        public string $mimeType,
    ){}
}
