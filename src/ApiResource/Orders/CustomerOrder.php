<?php

namespace App\ApiResource\Orders;

final readonly class CustomerOrder
{
    /**
     * @param array<string, mixed>    $shippingInfo
     * @param list<CustomerOrderItem> $items
     */
    public function __construct(
        public int $id,
        public string $reference,
        public string $status,
        public int $subtotal,
        public int $fees,
        public int $tva,
        public int $total,
        public string $currency,
        public string $createdAt,
        public array $shippingInfo,
        public ?string $invoiceUrl,
        public array $items,
    ) {
    }
}
