<?php

namespace App\Tests\UI\Tabs;

use App\UI\Tabs\TabMapper;
use PHPUnit\Framework\TestCase;

final class TabMapperTest extends TestCase
{
    public function testItMapsTheClothesCatalogTabs(): void
    {
        $tabs = (new TabMapper())->map(
            controllerRoute: 'app_clothes',
            bestsellerOnly: true,
        );

        self::assertSame('Actions du catalogue de vêtements', $tabs->ariaLabel);
        self::assertCount(6, $tabs->items);
        self::assertSame('app_clothe_add', $tabs->items[0]->route);
        self::assertTrue($tabs->items[2]->isActive);
    }
}
