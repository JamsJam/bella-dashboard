<?php

namespace App\Provider\Menu;

use App\Builder\Menu\MenuBuilder;
use App\Processor\Menu\MenuProcessor;

class DashboardMenuProvider
{
    public function __construct(
        private MenuBuilder $menuBuilder,
        private MenuProcessor $menuProcessor,
    ) {
    }

    /**
     * Recupere le menu du dashboard selon menu.yaml.
     *
     * @return MenuItemDTO[]
     */
    public function getMenu()
    {
        $menu = $this->menuBuilder->buildMenu();

        return $this->menuProcessor->process($menu);
    }
}
