<?php

namespace App\Application\PageGenerator\Blocks;

interface BlockInterface
{
    public function getType(): string;

    public function getData(): array; // données pour Twig

    public function getTemplate(): string;
}
