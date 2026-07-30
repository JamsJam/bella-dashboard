<?php

namespace App\Application\Orders\Delivery;

final readonly class AutoDeliverShippedOrderMessage
{
    public function __construct(
        public int $orderId,
        public string $trackingNumber,
    ) {
    }
}
