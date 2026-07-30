<?php

namespace App\Application\Orders\Delivery;

use Symfony\Component\Clock\ClockInterface;

final readonly class DeliveryDatePolicy
{
    public const TIMEZONE = 'Europe/Paris';
    public const MINIMUM_LEAD_DAYS = 2;

    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function minimumDeliveryDate(): \DateTimeImmutable
    {
        return $this->clock
            ->now()
            ->setTimezone(new \DateTimeZone(self::TIMEZONE))
            ->setTime(0, 0)
            ->modify(sprintf('+%d days', self::MINIMUM_LEAD_DAYS));
    }

    public function isAllowed(?\DateTimeImmutable $deliveryDate): bool
    {
        return $deliveryDate !== null
            && $deliveryDate->setTimezone(new \DateTimeZone(self::TIMEZONE))->setTime(0, 0) >= $this->minimumDeliveryDate();
    }

    public function reminderAt(\DateTimeImmutable $deliveryDate): \DateTimeImmutable
    {
        return $deliveryDate
            ->setTimezone(new \DateTimeZone(self::TIMEZONE))
            ->modify('-1 day')
            ->setTime(7, 0);
    }

    public function reminderDelayMilliseconds(\DateTimeImmutable $deliveryDate): int
    {
        return max(
            0,
            ($this->reminderAt($deliveryDate)->getTimestamp() - $this->clock->now()->getTimestamp()) * 1000,
        );
    }
}
