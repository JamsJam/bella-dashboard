<?php

namespace App\DTO\Clothes\Collections;

final class SizeGuideItemDTO 
{
    private ?string $clotheSize = null;
    
    private ?string $title = null;

    private ?string $size = null;

    /**
     * Get the value of clotheSize
     */ 
    public function getClotheSize()
    {
        return $this->clotheSize;
    }

    /**
     * Set the value of clotheSize
     *
     * @return  self
     */ 
    public function setClotheSize($clotheSize)
    {
        $this->clotheSize = $clotheSize;

        return $this;
    }

    /**
     * Get the value of title
     */ 
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */ 
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of size
     */ 
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set the value of size
     *
     * @return  self
     */ 
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }
}
