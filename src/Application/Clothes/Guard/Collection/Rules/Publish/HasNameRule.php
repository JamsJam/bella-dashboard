<?php

namespace App\Application\Clothes\Guard\Collection\Rules\Publish;

use App\Entity\Collections\Collections;

final readonly class HasNameRule implements CollectionPublishRuleInterface
{
    public function validate(Collections $collection): ?string
    {
        return trim((string) $collection->getName()) !== ''
            ? null
            : 'Le nom de la collection est requis.';
    }
}
