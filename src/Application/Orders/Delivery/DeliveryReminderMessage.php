<?php

namespace App\Application\Orders\Delivery;

final readonly class DeliveryReminderMessage
{
    public function __construct(
        public int $orderId,
        public string $deliveryDate,
    ) {
    }
}
