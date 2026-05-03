<?php

namespace App\Application\PageGenerator\Page;

use App\Application\PageGenerator\Blocks\BlockProvider;

final class PageBuilder
{
    private array $blocks = [];
    private BlockProvider $blockProvider;

    public function __construct(BlockProvider $blockProvider)
    {
        $this->blockProvider = $blockProvider;
    }

    public function fromYaml(array $yamlConfig, array $params = []): self
    {
        foreach ($yamlConfig['page'] as $blockConfig) {
            $this->blocks[] = $this->blockProvider->createBlock($blockConfig, $params);
        }

        return $this;
    }

    public function build(): Page
    {
        return new Page($this->blocks);
    }
}
