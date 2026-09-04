<?php

namespace App\State\Orders;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Orders\CheckoutVat;
use App\Application\Config\Provider\OrdersConfigProvider;

/** @implements ProviderInterface<CheckoutVat> */
final readonly class CheckoutVatProvider implements ProviderInterface
{
    public function __construct(
        private OrdersConfigProvider $ordersConfigProvider,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CheckoutVat
    {
        return new CheckoutVat($this->ordersConfigProvider->get()->vat);
    }
}
