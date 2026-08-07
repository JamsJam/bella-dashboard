<?php

namespace App\Application\Upload\Services;

use App\Application\Upload\Model\ChunkUploadRequest;
use App\Application\Upload\Model\ChunkUploadState;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ChunkedUploadService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function storeChunk(ChunkUploadRequest $request): ChunkUploadState
    {
        $tempDir = $this->getTempDir($request->fileId);

        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new \RuntimeException('Unable to create temporary upload directory.');
        }

        $request->chunk->move($tempDir, 'chunk_' . $request->chunkIndex);

        $receivedChunks = $this->countReceivedChunks($request->fileId, $request->totalChunks);

        return new ChunkUploadState(
            complete: $receivedChunks === $request->totalChunks,
            receivedChunks: $receivedChunks,
            totalChunks: $request->totalChunks,
        );
    }

    public function rebuild(ChunkUploadRequest $request, string $finalPath): void
    {
        $tempDir = $this->getTempDir($request->fileId);
        $output = fopen($finalPath, 'wb');

        if (false === $output) {
            throw new \RuntimeException('Unable to open final file.');
        }

        try {
            for ($index = 0; $index < $request->totalChunks; ++$index) {
                $chunkPath = $tempDir . '/chunk_' . $index;
                $input = fopen($chunkPath, 'rb');

                if (false === $input) {
                    throw new \RuntimeException(sprintf('Unable to open chunk "%s".', $chunkPath));
                }

                stream_copy_to_stream($input, $output);
                fclose($input);
            }
        } finally {
            fclose($output);
        }
    }

    public function cleanup(string $fileId): void
    {
        $tempDir = $this->getTempDir($fileId);

        if (!is_dir($tempDir)) {
            return;
        }

        foreach (glob($tempDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        @rmdir($tempDir);
    }

    private function countReceivedChunks(string $fileId, int $totalChunks): int
    {
        $tempDir = $this->getTempDir($fileId);
        $receivedChunks = 0;

        for ($index = 0; $index < $totalChunks; ++$index) {
            if (is_file($tempDir . '/chunk_' . $index)) {
                ++$receivedChunks;
            }
        }

        return $receivedChunks;
    }

    private function getTempDir(string $fileId): string
    {
        return $this->projectDir . '/var/avatar-upload-chunks/' . $fileId;
    }
}
