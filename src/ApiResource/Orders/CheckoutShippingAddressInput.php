<?php

namespace App\ApiResource\Orders;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutShippingAddressInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $firstName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $lastName = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public string $phone = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $address = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public string $postcode = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $city = '';
}
