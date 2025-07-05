<?php

namespace App\Twig\Components\Dashboard\Menu;

use App\DTO\Menu\MenuItemDTO;
use App\Provider\Menu\DashboardMenuProvider;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Menu
{
    public function __construct(
        private DashboardMenuProvider $menuProvider,
    ) {
    }

    public ?string $routeKey = null;

    /**
     * Undocumented function.
     *
     * @return MenuItemDTO[]
     */
    public function getMenu(): array
    {
        return $this->menuProvider->getMenu();
    }
}
