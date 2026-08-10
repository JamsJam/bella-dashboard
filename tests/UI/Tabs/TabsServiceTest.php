<?php

namespace App\Tests\UI\Tabs;

use App\UI\Tabs\TabMapper;
use App\UI\Tabs\TabsService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class TabsServiceTest extends TestCase
{
    public function testItUsesTheCurrentControllerRoute(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(
            query: ['bestseller' => '1'],
            attributes: ['_route' => 'app_clothes'],
        ));

        $tabs = (new TabsService(
            $requestStack,
            new TabMapper(),
        ))->create();

        self::assertCount(6, $tabs->items);
        self::assertTrue($tabs->items[2]->isActive);
    }
}
