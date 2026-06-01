<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasSizeRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return $clothe->getSize() !== null ? null : 'La taille est requise.';
    }
}
