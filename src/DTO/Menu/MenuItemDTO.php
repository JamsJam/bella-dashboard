<?php

namespace App\DTO\Menu;

class MenuItemDTO
{

    private ?string $label  = null;

    private ?string $route = null ;

    private ?string $class  = null;

    private ?string $activClass = null ;
    
    
    private ?string $ariaLabel = null ;

    private ?string $key = null ;

    // private ?string $icon = null ;

    private array $roles = [] ;
    
    // private array $children = [] ;


  

    /**
     * Get the value of label
     */ 
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set the value of label
     *
     * @return  self
     */ 
    public function setLabel($label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of route
     */ 
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * Set the value of route
     *
     * @return  self
     */ 
    public function setRoute($route): self
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get the value of class
     */ 
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Set the value of class
     *
     * @return  self
     */ 
    public function setClass($class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Get the value of activClass
     */ 
    public function getActivClass()
    {
        return $this->activClass;
    }

    /**
     * Set the value of activClass
     *
     * @return  self
     */ 
    public function setActivClass($activClass): self
    {
        $this->activClass = $activClass;

        return $this;
    }

        /**
     * Get the value of ariaLabel
     */ 
    public function getAriaLabel()
    {
        return $this->ariaLabel;
    }

    /**
     * Set the value of ariaLabel
     *
     * @return  self
     */ 
    public function setAriaLabel($ariaLabel): self
    {
        $this->ariaLabel = $ariaLabel;

        return $this;
    }
    /**
     * Get the value of key
     */ 
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value of key
     *
     * @return  self
     */ 
    public function setKey($key): self
    {
        $this->key = $key;

        return $this;
    }

    // /**
    //  * Get the value of icon
    //  */ 
    // public function getIcon()
    // {
    //     return $this->icon;
    // }

    // /**
    //  * Set the value of icon
    //  *
    //  * @return  self
    //  */ 
    // public function setIcon($icon): self
    // {
    //     $this->icon = $icon;

    //     return $this;
    // }

    /**
     * Get the value of roles
     */ 
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set the value of roles
     *
     * @return  self
     */ 
    public function setRoles($roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    // /**
    //  * Get the value of children
    //  */ 
    // public function getChildren()
    // {
    //     return $this->children;
    // }

    // /**
    //  * Set the value of children
    //  *
    //  * @return  self
    //  */ 
    // public function setChildren($children): self
    // {
    //     $this->children = $children;

    //     return $this;
    // }


}