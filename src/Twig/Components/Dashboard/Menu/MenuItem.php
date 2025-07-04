<?php

namespace App\Twig\Components\Dashboard\Menu;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class MenuItem
{
    public ?string $label  = null;

    public ?string $route = null ;

    public ?string $class  = null;

    public ?string $activClass = null ;
    
    public ?string $ariaLabel = null ;

    public ?string $key = null ;

    public ?string $routeKey = null ;

    // public ?string $icon = null ;

    // public array $roles = [] ;
    
    // public array $children = [] ;
}
