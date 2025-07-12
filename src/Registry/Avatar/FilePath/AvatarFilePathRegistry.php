<?php

namespace App\Registry\Avatar\FilePath;

class AvatarFilePathRegistry
{
    public function __construct(
        private string $targetDirectory,
    ) {
    }

    public function getHairFilePathDirectory($name): string
    {
        $nameChunks = explode('__', $name);
        $path = $this->getTargetDirectory().'/'.$nameChunks[0].'/'.$nameChunks[1].'/'.$nameChunks[2];

        return $path;
    }

    public function getBodyFilePathDirectory($name): string
    {
        $nameChunks = explode('__', $name);
        $path = $this->getTargetDirectory().'/'.$nameChunks[0].'/'.$nameChunks[1].'/'.$nameChunks[2].'/'.$nameChunks[3].'/'.explode('.', $nameChunks[4])[0];

        return $path;
    }

    public function getPartFilePathDirectory($name): string
    {
        $nameChunks = explode('__', $name);
        $path = $this->getTargetDirectory().'/'.$nameChunks[0].'/'.$nameChunks[1].'/'.explode('.', $nameChunks[2])[0];

        return $path;
    }

    private function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
}
