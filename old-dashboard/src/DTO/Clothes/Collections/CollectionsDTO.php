<?php

namespace App\DTO\Clothes\Collections;

use App\Entity\Category\Category;
use App\DTO\Clothes\Clothes\ClotheDTO;
use App\DTO\Clothes\Clothes\ClothesDTO;
use Symfony\Component\HttpFoundation\File\File;

final class CollectionsDTO 
{
    private ?string $name = null;

    private ?File $image = null;

    private ?Category $category = null;
    /**
     * Clothe collection
     *
     * @var ClothesDTO[]
     */
    private array $clothes = [];

    /**
     * Clothe sizeGuide 
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
    public function getSizeGuide():array
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
    public function setSizeGuide(array $sizeGuide):self
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
    public function addSizeGuide(SizeGuideItemDTO $sizeGuide):self
    {
        $this->sizeGuide[] = $sizeGuide;

        return $this;
    }

    /**
     * Get clothe collection
     *
     * @return  ClothesDTO[]
     */ 
    public function getClothes() :array
    {
        return $this->clothes;
    }

    /**
     * Set clothe collection
     *
     * @param  ClotheDTO[]  $clothes  Clothe collection
     *
     * @return  self
     */ 
    public function setClothes(array $clothes):self
    {
        $this->clothes[] = $clothes;

        return $this;
    }

    /**
     * Get the value of category
     */ 
    public function getCategory(): ?Category
    {
        return $this->category;
    }

    /**
     * Set the value of category
     *
     * @return  self
     */ 
    public function setCategory($category):self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get the value of image
     */ 
    public function getImage():?File
    {
        return $this->image;
    }

    /**
     * Set the value of image
     *
     * @return  self
     */ 
    public function setImage($image):self
    {
        $this->image = $image;

        return $this;
    }
}