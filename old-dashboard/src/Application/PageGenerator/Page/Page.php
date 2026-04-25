<?php

namespace App\Application\PageGenerator\Page;

use App\Application\PageGenerator\Blocks\BlockInterface;

class Page
{
    /** @var BlockInterface[] */
    private array $blocks;

    public function __construct(array $blocks = [])
    {
        $this->blocks = $blocks;
    }

    /** @return BlockInterface[] */
    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function addBlock(BlockInterface $block): void
    {
        $this->blocks[] = $block;
    }
}
