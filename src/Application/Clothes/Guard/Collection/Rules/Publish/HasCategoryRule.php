<?php

namespace App\Application\Clothes\Guard\Collection\Rules\Publish;

use App\Entity\Collections\Collections;

final readonly class HasCategoryRule implements CollectionPublishRuleInterface
{
    public function validate(Collections $collection): ?string
    {
        return $collection->getCategory() !== null
            ? null
            : 'La categorie de la collection est requise.';
    }
}
