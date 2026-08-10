<?php

namespace App\UI\Tabs;

final readonly class TabsDto
{
    /**
     * @param list<TabDto> $items
     */
    public function __construct(
        public array $items,
        public string $ariaLabel = 'Actions',
    ) {
    }
}
