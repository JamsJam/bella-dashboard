<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasColorRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return $clothe->getColor() !== null ? null : 'La couleur est requise.';
    }
}
