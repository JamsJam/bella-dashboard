<?php

namespace App\ApiResource\Orders;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Orders\CustomerOrdersProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/customers/orders',
            security: 'is_granted("ROLE_USER")',
            provider: CustomerOrdersProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class CustomerOrderList
{
    /** @param list<CustomerOrder> $orders */
    public function __construct(
        public array $orders,
    ) {
    }
}
