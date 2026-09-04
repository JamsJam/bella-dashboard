<?php

namespace App\ApiResource\Orders;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\Orders\ShippingCountryProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/checkout/contry',
            provider: ShippingCountryProvider::class,
        ),
    ],
    formats: ['json' => ['application/json']],
)]
final readonly class ShippingCountryList
{
    /** @param list<ShippingCountry> $countries */
    public function __construct(
        public array $countries,
    ) {
    }
}
