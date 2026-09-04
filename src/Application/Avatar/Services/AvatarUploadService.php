<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Model\AvatarUploadResult;
use App\Application\Upload\Model\ChunkUploadRequest;
use App\Application\Upload\Services\ChunkedUploadService;
use App\Entity\AvatarTemp;
use App\Service\FileManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class AvatarUploadService
{
    public function __construct(
        private ChunkedUploadService $chunkedUploadService,
        private AvatarImageUploadValidator $avatarImageUploadValidator,
        private EntityManagerInterface $entityManager,
        private FileManagerService $fileManager,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function handleChunkUpload(Request $request): AvatarUploadResult
    {
        $chunkUploadRequest = $this->createChunkUploadRequest($request);

        if (!$chunkUploadRequest instanceof ChunkUploadRequest) {
            return AvatarUploadResult::error('Missing chunk', Response::HTTP_BAD_REQUEST);
        }

        $validationError = $this->avatarImageUploadValidator->validateMetadata($chunkUploadRequest);
        if (null !== $validationError) {
            return AvatarUploadResult::error($validationError, Response::HTTP_BAD_REQUEST);
        }

        try {
            $state = $this->chunkedUploadService->storeChunk($chunkUploadRequest);
        } catch (\Throwable) {
            return AvatarUploadResult::error('Unable to create temporary upload directory', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$state->complete) {
            return new AvatarUploadResult(
                success: true,
                status: 'chunk_received',
                extra: [
                    'fileId' => $chunkUploadRequest->fileId,
                    'chunkIndex' => $chunkUploadRequest->chunkIndex,
                    'receivedChunks' => $state->receivedChunks,
                    'totalChunks' => $state->totalChunks,
                ],
            );
        }

        return $this->rebuildAvatarFile($chunkUploadRequest);
    }

    private function createChunkUploadRequest(Request $request): ?ChunkUploadRequest
    {
        $chunk = $request->files->get('chunk');

        if (!$chunk instanceof UploadedFile) {
            return null;
        }

        $originalName = (string) $request->request->get('originalName', '');

        return new ChunkUploadRequest(
            chunk: $chunk,
            fileId: (string) $request->request->get('fileId', ''),
            originalName: $originalName,
            relativePath: (string) $request->request->get('relativePath', $originalName),
            chunkIndex: $request->request->getInt('chunkIndex', -1),
            totalChunks: $request->request->getInt('totalChunks', 0),
            fileSize: $request->request->getInt('fileSize', 0),
            mimeType: (string) $request->request->get('mimeType', ''),
        );
    }

    private function rebuildAvatarFile(ChunkUploadRequest $request): AvatarUploadResult
    {
        $uploadDir = $this->projectDir . '/var/avatar-temp/' . $request->fileId;

        try {
            $this->fileManager->ensureDirectory($uploadDir);
        } catch (\Throwable) {
            $this->chunkedUploadService->cleanup($request->fileId);

            return AvatarUploadResult::error('Unable to create avatar temporary directory', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $storedName = $this->createStoredImageFilename($request->originalName);
        $finalPath = $this->createAvailablePath($uploadDir, $storedName);

        try {
            $this->chunkedUploadService->rebuild($request, $finalPath);
        } catch (\Throwable) {
            $this->chunkedUploadService->cleanup($request->fileId);

            return AvatarUploadResult::error('Unable to rebuild uploaded file', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (filesize($finalPath) !== $request->fileSize || !$this->avatarImageUploadValidator->isValidPngFile($finalPath)) {
            $this->fileManager->remove($finalPath);
            $this->chunkedUploadService->cleanup($request->fileId);

            return AvatarUploadResult::error('Invalid PNG file', Response::HTTP_BAD_REQUEST);
        }

        $this->chunkedUploadService->cleanup($request->fileId);

        $avatarTemp = (new AvatarTemp())
            ->setOriginalName($request->originalName)
            ->setStoredName(basename($finalPath))
            ->setRelativePath($request->relativePath)
            ->setTempPath($finalPath)
            ->setMimeType('' !== $request->mimeType ? $request->mimeType : 'image/png')
            ->setFileSize($request->fileSize)
            ->setExtension('png')
            ->setStatus('uploaded');

        $this->entityManager->persist($avatarTemp);
        $this->entityManager->flush();

        return new AvatarUploadResult(
            success: true,
            status: 'file_uploaded',
            extra: [
                'avatarTempId' => $avatarTemp->getId(),
                'originalName' => $avatarTemp->getOriginalName(),
                'storedName' => $avatarTemp->getStoredName(),
            ],
        );
    }

    private function createSafeImageFilename(string $originalName): string
    {
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $slugger = new AsciiSlugger();
        $safeName = strtolower((string) $slugger->slug($baseName));

        if ('' === $safeName) {
            $safeName = 'avatar';
        }

        return $safeName . '.png';
    }

    private function createStoredImageFilename(string $originalName): string
    {
        $baseName = pathinfo($this->createSafeImageFilename($originalName), PATHINFO_FILENAME);

        return $baseName . '-' . bin2hex(random_bytes(4)) . '.png';
    }

    private function createAvailablePath(string $uploadDir, string $filename): string
    {
        $path = $uploadDir . '/' . $filename;

        if (!$this->fileManager->exists($path)) {
            return $path;
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        return $uploadDir . '/' . $baseName . '-' . bin2hex(random_bytes(6)) . '.png';
    }
}
