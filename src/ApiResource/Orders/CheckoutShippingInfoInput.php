<?php

namespace App\ApiResource\Orders;

use Symfony\Component\Validator\Constraints as Assert;

final class CheckoutShippingInfoInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $surname = null;

    #[Assert\Length(max: 100)]
    public ?string $shippingTitle = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $shippingAddress = null;

    #[Assert\Length(max: 255)]
    public ?string $shippingAddress2 = null;

    #[Assert\Length(max: 255)]
    public ?string $lieuDit = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public ?string $postalCode = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $city = null;

    #[Assert\Length(max: 8)]
    public ?string $selectedTel = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    public ?string $tel = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    public ?string $country = null;

    #[Assert\Date]
    public ?string $deliveryDate = null;

    #[Assert\PositiveOrZero]
    public int|float|null $selectedDelivery = null;
}
