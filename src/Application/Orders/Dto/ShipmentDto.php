<?php

namespace App\Application\Orders\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class ShipmentDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le numéro d’expédition est obligatoire.')]
        #[Assert\Length(max: 255)]
        public string $trackingNumber = '',
        #[Assert\NotBlank(message: 'Le transporteur est obligatoire.')]
        public string $carrier = '',
    ) {
    }
}
