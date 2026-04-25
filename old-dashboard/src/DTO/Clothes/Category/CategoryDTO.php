<?php

namespace App\DTO\Clothes\Category;

use App\DTO\Clothes\Collections\CollectionsDTO;
use Symfony\Component\HttpFoundation\File\File;



final class CategoryDTO 
{
    private ?string $name = null;

    private ?File $image = null;

    private ?string $metaDescription = null;

    /**
     * @var CollectionsDTO[]
     */
    private array $collections = [];

    /**
     * Get the value of image
     */ 
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set the value of image
     *
     * @return  self
     */ 
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

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
     * Get the value of metaDescription
     */ 
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     * Set the value of metaDescription
     *
     * @return  self
     */ 
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }
    
    /**
     * Get the value of collection
     *
     * @return  CollectionsDTO[]
     */ 
    public function getCollections()
    {
        return $this->collections;
    }

    /**
     * Set the value of collection
     *
     * @param  CollectionsDTO[]  $collections
     *
     * @return  self
     */ 
    public function setCollections(array $collections)
    {
        $this->collections = $collections;

        return $this;
    }

    /**
     * Set the value of collection
     *
     * @param  CollectionsDTO[]  $collections
     *
     * @return  self
     */ 
    public function addCollections(CollectionsDTO $collections)
    {
        $this->collections[] = $collections;

        return $this;
    }

}
