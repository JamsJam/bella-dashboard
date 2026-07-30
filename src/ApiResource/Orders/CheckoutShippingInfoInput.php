<?php

namespace App\ApiResource\Orders;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutShippingInfoInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $name = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $surname = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $shippingTitle = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $shippingAddress = '';

    #[Assert\Length(max: 255)]
    public string $shippingAddress2 = '';

    #[Assert\Length(max: 255)]
    public string $lieuDit = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public string $postalCode = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public string $city = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 8)]
    public string $selectedTel = '';

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public string $tel = '';

    #[Assert\NotBlank]
    #[Assert\Country]
    public string $country = '';

    #[Assert\Date]
    public ?string $deliveryDate = null;

    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    public int|float|null $selectedDelivery = null;
}
