<?php

namespace App\Tests\Application\Orders\Delivery;

use App\Application\Orders\Delivery\DeliveryDatePolicy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class DeliveryDatePolicyTest extends TestCase
{
    public function testMinimumDateAndReminderTime(): void
    {
        $policy = new DeliveryDatePolicy(
            new MockClock('2026-07-30 12:00:00 Europe/Paris'),
        );

        self::assertSame('2026-08-01', $policy->minimumDeliveryDate()->format('Y-m-d'));
        self::assertFalse($policy->isAllowed(new \DateTimeImmutable('2026-07-31 Europe/Paris')));
        self::assertTrue($policy->isAllowed(new \DateTimeImmutable('2026-08-01 Europe/Paris')));
        self::assertSame(
            '2026-07-31 07:00:00',
            $policy->reminderAt(new \DateTimeImmutable('2026-08-01 Europe/Paris'))->format('Y-m-d H:i:s'),
        );
    }
}
