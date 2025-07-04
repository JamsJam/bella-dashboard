<?php

namespace App\Twig\Components\Dashboard\Menu;

use App\DTO\Menu\MenuItemDTO;
use App\Builder\Menu\MenuBuilder;
use App\Processor\Menu\MenuProcessor;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Menu
{

    public function __construct(
        private MenuBuilder $menuBuilder,
        private MenuProcessor $menuProcessor
    ){}

        public ?string $routeKey = null ;

    /**
     * Undocumented function
     *
     * @return MenuItemDTO[]
     */
    public function getMenu(): array
    {
        return $this->menuProcessor->process($this->menuBuilder->buildMenu());
    }

}
