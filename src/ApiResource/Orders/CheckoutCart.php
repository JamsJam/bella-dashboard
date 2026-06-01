<?php

namespace App\ApiResource\Orders;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\State\Orders\CreateCheckoutCartProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/checkout/carts',
            security: 'is_granted("ROLE_USER")',
            input: CheckoutCartInput::class,
            output: CheckoutCartOutput::class,
            processor: CreateCheckoutCartProcessor::class,
        ),
    ],
)]
final class CheckoutCart
{
}
