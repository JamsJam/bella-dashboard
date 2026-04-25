<?php

namespace App\Service;


use App\Service\FileService\FileManagerService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadedFileService
{
    public function __construct(
        private FileManagerService $fileManagerService,
    ) {
    }

    public function changeName($path, $entity, $relationEntity, $file)
    {
    }

    public function createDirectory()
    {
    }

    public function move(string $path, UploadedFile $file)
    {
        $this->fileManagerService->createFolder($path);
        $file->move($path, $file->getClientOriginalName());
    }


    public function processFile(UploadedFile $file){

    }
}
