<?php

namespace App\Application\PageGenerator\Blocks\Back;

use App\Application\PageGenerator\Blocks\BlockInterface;

final class AdminTabsBlock implements BlockInterface
{
    public function __construct(
        public array $tabs,
        public string $theme,
        public bool $reverse,
    ) {
    }

    public function getType(): string
    {
        return 'adminTabsBlock';
    }

    public function getData(): array
    {
        return [
            'tabs' => $this->tabs,
            'theme' => $this->theme,
            'reverse' => $this->reverse,
        ];
    }

    public function getTemplate(): string
    {
        return 'partials/blocks/admin/adminTabsBlock.html.twig';
    }
}
