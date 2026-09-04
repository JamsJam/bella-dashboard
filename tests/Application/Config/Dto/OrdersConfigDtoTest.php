<?php

namespace App\Tests\Application\Config\Dto;

use App\Application\Config\Dto\OrdersConfigDto;
use PHPUnit\Framework\TestCase;

final class OrdersConfigDtoTest extends TestCase
{
    public function testItLoadsAndSerializesCarriers(): void
    {
        $config = OrdersConfigDto::fromArray([
            'carriers' => [['name' => 'Transport express', 'tracking_url' => 'https://example.com/track/']],
        ]);

        self::assertCount(1, $config->carriers);
        self::assertSame('Transport express', $config->carriers[0]->name);
        self::assertSame('https://example.com/track/', $config->carriers[0]->trackingUrl);
        self::assertSame([
            'name' => 'Transport express',
            'tracking_url' => 'https://example.com/track/',
        ], $config->toArray()['carriers'][0]);
    }

    public function testItProvidesDefaultCarriersForAnExistingConfiguration(): void
    {
        $config = OrdersConfigDto::fromArray([]);

        self::assertSame(['La Poste', 'Colissimo', 'DPD'], array_map(
            static fn ($carrier): string => $carrier->name,
            $config->carriers,
        ));
    }
}
