<?php

namespace App\Application\Clothes\Guard\Category\Rules\Publish;

use App\Entity\Category\Category;

final readonly class HasSlugRule implements CategoryPublishRuleInterface
{
    public function validate(Category $category): ?string
    {
        return trim((string) $category->getSlug()) !== ''
            ? null
            : 'Le slug de la categorie est requis.';
    }
}
