<?php

namespace App\DTO\Clothes\Collections;



final class CollectionsDTO 
{
    private ?string $name = null;

    /**
     * Undocumented variable
     *
     * @var SizeGuideItemDTO[]
     */
    private array $sizeGuide = [];


    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  SizeGuideItemDTO[]
     */ 
    public function getSizeGuide()
    {
        return $this->sizeGuide;
    }

    /**
     * Set undocumented variable
     *
     * @param  SizeGuideItemDTO[]  $sizeGuide  
     *
     * @return  self
     */ 
    public function setSizeGuide(array $sizeGuide)
    {
        $this->sizeGuide = $sizeGuide;

        return $this;
    }
    /**
     * Set undocumented variable
     *
     * @param  SizeGuideItemDTO  $sizeGuide  
     *
     * @return  self
     */ 
    public function addSizeGuide(SizeGuideItemDTO $sizeGuide)
    {
        $this->sizeGuide[] = $sizeGuide;

        return $this;
    }
}