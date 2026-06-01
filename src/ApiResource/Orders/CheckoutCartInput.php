<?php

namespace App\ApiResource\Orders;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutCartInput
{
    /**
     * @var array<int, array{productId: int, name: string, quantity: int, unitPriceTTC: int}>
     */
    #[Assert\NotBlank]
    #[Assert\Count(min: 1)]
    public array $items = [];

    #[Assert\Currency]
    public string $currency = 'EUR';
}
