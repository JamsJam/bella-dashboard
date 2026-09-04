<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasCollectionRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return null !== $clothe->getCollection() ? null : 'La collection est requise.';
    }
}
