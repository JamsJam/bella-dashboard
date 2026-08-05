<?php

namespace App\Application\Clothes\DTO;

use App\Entity\Clothes\Clothes;
use Symfony\Component\Validator\Constraints as Assert;

final class VariantFormInput extends VariantGroupInput
{
    #[Assert\NotNull(message: 'Sélectionnez un vêtement.')]
    public ?Clothes $clothe = null;
}
