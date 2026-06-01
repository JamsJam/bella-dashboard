<?php

namespace App\Application\Clothes\Guard\Category\Rules\Publish;

use App\Entity\Category\Category;

final readonly class HasImageRule implements CategoryPublishRuleInterface
{
    public function validate(Category $category): ?string
    {
        return trim((string) $category->getImage()) !== ''
            ? null
            : 'Une image de categorie est requise.';
    }
}
