<?php

namespace App\ApiResource\Orders;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutCartInput
{
    /**
     * @var array<int, array{variantId: int, quantity: int}>
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $items = [];

    #[Assert\Currency]
    public string $currency = 'EUR';

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $shippingDestination = '';

    #[Assert\NotNull]
    #[Assert\Valid]
    public ?CheckoutShippingInfoInput $shippingInfo = null;
}
