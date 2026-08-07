<?php

namespace App\Application\Clothes\Guard\Category\Rules\Publish;

use App\Entity\Category\Category;

final readonly class HasNameRule implements CategoryPublishRuleInterface
{
    public function getLabel(): string
    {
        return 'Nom de la catégorie renseigné';
    }

    public function validate(Category $category): ?string
    {
        return '' !== trim((string) $category->getName())
            ? null
            : 'Le nom de la categorie est requis.';
    }
}
