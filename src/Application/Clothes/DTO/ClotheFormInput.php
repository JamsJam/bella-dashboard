<?php

namespace App\Application\Clothes\DTO;

use App\Entity\Collections\Collections;
use Symfony\Component\Validator\Constraints as Assert;

final class ClotheFormInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 70)]
    public ?string $name = null;

    #[Assert\Positive(message: 'Le prix doit être supérieur à zéro.')]
    public ?int $price = null;

    #[Assert\NotNull(message: 'Sélectionnez une collection.')]
    public ?Collections $collection = null;

    /** @var list<VariantGroupInput> */
    #[Assert\Valid]
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins un groupe de variantes.')]
    public array $variants = [];
}
