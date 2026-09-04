<?php

namespace App\Application\Clothes\Guard\Category\Rules\Publish;

use App\Application\Clothes\Guard\Collection\CollectionOnlineGuard;
use App\Entity\Category\Category;
use App\Entity\Collections\Collections;

final readonly class HasPublishableCollectionRule implements CategoryPublishRuleInterface
{
    public function __construct(
        private CollectionOnlineGuard $collectionOnlineGuard,
    ) {
    }

    public function getLabel(): string
    {
        return 'Au moins une collection publiable';
    }

    public function validate(Category $category): ?string
    {
        foreach ($category->getCollections() as $collection) {
            if ($collection instanceof Collections && $this->collectionOnlineGuard->canPublish($collection)->canPublish()) {
                return null;
            }
        }

        return 'La catégorie doit contenir au moins une collection publiable.';
    }
}
