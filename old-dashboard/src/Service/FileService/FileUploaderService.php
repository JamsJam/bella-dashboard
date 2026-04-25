<?php
namespace App\Service\FileService;

use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use App\Service\FileService\FileManagerService;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class FileUploaderService
{
    public function __construct(
        // private string $targetDirectory,
        private FileManagerService $fileManagerService,
        private SluggerInterface $slugger,
        private LoggerInterface $logger,
    ) {
    }

    public function upload(UploadedFile $file, string $targetDirectory, string $name): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {

            $this->fileManagerService->createFolder($targetDirectory);
            $file->move($targetDirectory."/".$this->slugger->slug($name), $fileName);
        } catch (FileException $e) {
            $this->logger->error('File upload failed: '.$e->getMessage());
            throw new \RuntimeException('Upload failed.');
        }

        return $fileName;
    }


}