<?php

namespace App\Tests\Application\Avatar\Services;

use App\Application\Avatar\Services\AvatarPartSortService;
use PHPUnit\Framework\TestCase;

final class AvatarPartSortServiceTest extends TestCase
{
    public function testItSortsBodyPartsByMorphology(): void
    {
        $items = [
            ['id' => 1, 'name' => 'body__clair__rectangle__moyen__robe'],
            ['id' => 2, 'name' => 'body__clair__sablier__moyen__chemise'],
        ];

        self::assertSame(
            [1, 2],
            array_column((new AvatarPartSortService())->sort($items, 'body', 'morphologie', 'asc'), 'id'),
        );
    }

    public function testItSortsBodyPartsByClothesDescending(): void
    {
        $items = [
            ['id' => 1, 'name' => 'body__clair__rectangle__moyen__chemise'],
            ['id' => 2, 'name' => 'body__clair__rectangle__moyen__robe'],
        ];

        self::assertSame(
            [2, 1],
            array_column((new AvatarPartSortService())->sort($items, 'body', 'clothes', 'desc'), 'id'),
        );
    }

    public function testItSortsOtherPartsByColor(): void
    {
        $items = [
            ['id' => 1, 'name' => 'eye__vert__rond'],
            ['id' => 2, 'name' => 'eye__bleu__amande'],
        ];

        self::assertSame(
            [2, 1],
            array_column((new AvatarPartSortService())->sort($items, 'eyes', 'color', 'asc'), 'id'),
        );
    }

    public function testItDefaultsToNewestFirst(): void
    {
        $items = [
            ['id' => 1, 'name' => 'nose__clair__fin', 'createdAt' => new \DateTimeImmutable('2026-01-01')],
            ['id' => 2, 'name' => 'nose__clair__large', 'createdAt' => new \DateTimeImmutable('2026-02-01')],
        ];

        self::assertSame(
            [2, 1],
            array_column((new AvatarPartSortService())->sort($items, 'nose', null, null), 'id'),
        );
    }

    public function testOptionsDependOnAvatarPart(): void
    {
        $service = new AvatarPartSortService();

        self::assertSame(
            ['createdAt', 'skinColor', 'clothes', 'morphologie', 'morphotype'],
            array_column($service->optionsFor('body'), 'value'),
        );
        self::assertSame(
            ['createdAt', 'color', 'shape'],
            array_column($service->optionsFor('hair'), 'value'),
        );
    }
}
