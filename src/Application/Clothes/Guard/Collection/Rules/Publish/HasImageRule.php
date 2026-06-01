<?php

namespace App\Application\Clothes\Guard\Collection\Rules\Publish;

use App\Entity\Collections\Collections;

final readonly class HasImageRule implements CollectionPublishRuleInterface
{
    public function validate(Collections $collection): ?string
    {
        return trim((string) $collection->getImage()) !== ''
            ? null
            : 'Une image de collection est requise.';
    }
}
