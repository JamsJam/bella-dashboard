<?php

namespace App\Tests\Entity\Orders;

use App\Entity\Orders\Orders;
use PHPUnit\Framework\TestCase;

final class OrdersTrackingTest extends TestCase
{
    public function testItBuildsTheCarrierTrackingUrl(): void
    {
        $order = (new Orders())
            ->setTrackingNumber('AB 123/FR')
            ->setCarrierName('Colissimo')
            ->setCarrierTrackingUrl('https://example.com/track?number=');

        self::assertSame('Colissimo', $order->getCarrierName());
        self::assertSame('https://example.com/track?number=AB%20123%2FFR', $order->getTrackingUrl());
    }

    public function testTrackingUrlIsNullWithoutCompleteTrackingInformation(): void
    {
        self::assertNull((new Orders())->setTrackingNumber('AB123')->getTrackingUrl());
    }
}
