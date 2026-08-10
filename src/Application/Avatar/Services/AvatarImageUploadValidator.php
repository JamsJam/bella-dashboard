<?php

namespace App\Application\Avatar\Services;

use App\Application\Upload\Model\ChunkUploadRequest;
use App\Service\FileManagerService;

final class AvatarImageUploadValidator
{
    private const ALLOWED_MIME_TYPES = [
        'image/png',
        'application/octet-stream',
    ];

    public function __construct(
        private readonly FileManagerService $fileManager,
    ) {
    }

    public function validateMetadata(ChunkUploadRequest $request): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_-]{8,80}$/', $request->fileId)) {
            return 'Invalid file identifier';
        }

        if ('' === $request->originalName || !str_ends_with(strtolower($request->originalName), '.png')) {
            return 'Only PNG files are allowed';
        }

        if (
            '' === $request->relativePath
            || str_contains($request->relativePath, '..')
            || str_starts_with($request->relativePath, '/')
            || str_contains($request->relativePath, '\\')
        ) {
            return 'Invalid relative path';
        }

        if ($request->chunkIndex < 0 || $request->totalChunks < 1 || $request->chunkIndex >= $request->totalChunks) {
            return 'Invalid chunk metadata';
        }

        if ($request->fileSize < 1) {
            return 'Invalid file size';
        }

        if ('' !== $request->mimeType && !in_array($request->mimeType, self::ALLOWED_MIME_TYPES, true)) {
            return 'Invalid MIME type';
        }

        return null;
    }

    public function isValidPngFile(string $path): bool
    {
        if (!$this->fileManager->isFile($path) || 'png' !== strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            return false;
        }

        $signature = file_get_contents($path, false, null, 0, 8);
        if ("\x89PNG\r\n\x1A\n" !== $signature) {
            return false;
        }

        $mimeType = function_exists('finfo_open') ? $this->detectMimeType($path) : null;
        if (null !== $mimeType && 'image/png' !== $mimeType) {
            return false;
        }

        $imageInfo = @getimagesize($path);

        return is_array($imageInfo) && ($imageInfo[2] ?? null) === IMAGETYPE_PNG;
    }

    private function detectMimeType(string $path): ?string
    {
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        if (false === $fileInfo) {
            return null;
        }

        $mimeType = finfo_file($fileInfo, $path);
        finfo_close($fileInfo);

        return is_string($mimeType) ? $mimeType : null;
    }
}
