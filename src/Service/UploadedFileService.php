<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadedFileService 
{
    public function __construct(
        private Filesystem $filesystem
    ) {}

    public function changeName($path, $entity, $relationEntity, $file){
        
    }

    public function createDirectory(){

    }
    public function move(string $path, UploadedFile $file){
        
        
        $this->filesystem->mkdir($path);
        $file->move($path, $file->getClientOriginalName());


    }


}
