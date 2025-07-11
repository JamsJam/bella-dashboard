<?php

namespace App\DTO\Avatar;

final class hairImageDTO 
{
    private ?string $frontImage = null;
    
    private ?string $backImage = null;

    /**
     * Get the value of frontImage
     */ 
    public function getFrontImage():?string
    {
        return $this->frontImage;
    }

    /**
     * Set the value of frontImage
     *
     * @return  self
     */ 
    public function setFrontImage($frontImage): self
    {
        $this->frontImage = $frontImage;

        return $this;
    }

    /**
     * Get the value of backImage
     */ 
    public function getBackImage():?string
    {
        return $this->backImage;
    }

    /**
     * Set the value of backImage
     *
     * @return  self
     */ 
    public function setBackImage($backImage): self
    {
        $this->backImage = $backImage;

        return $this;
    }

    public function toArray(): array {
        return [
            'frontImage' => $this->getFrontImage(),
            'backImage' => $this->getBackImage(),
        ];
    }
}
