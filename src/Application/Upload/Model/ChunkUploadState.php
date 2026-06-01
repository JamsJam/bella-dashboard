<?php

namespace App\Application\Upload\Model;

final readonly class ChunkUploadState
{
    public function __construct(
        public bool $complete,
        public int $receivedChunks,
        public int $totalChunks,
    ) {
    }
}
