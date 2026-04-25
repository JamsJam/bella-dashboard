<?php
namespace App\Service\FileService;

use Symfony\Component\Filesystem\Filesystem;

class FileManagerService 
{
    public function __construct(
        private readonly Filesystem $filesystem
    ){}

    public function move()
    {}

    /**
     * Create a folder at the provided path
     *
     * @param string $targetDirectory
     * @return void
     * @throws IOException
     */
    public function createFolder(string $targetDirectory)
    {
        $this->filesystem->mkdir($targetDirectory);
    }
    
    /**
     * Remove a file or forlder
     *
     * @param string .$fileToRemove
     * @return void
     * @throws IOException
     */
    public function removeFile(string $pathToRemove):void
    {

        $this->filesystem->remove($pathToRemove);

    }
}
