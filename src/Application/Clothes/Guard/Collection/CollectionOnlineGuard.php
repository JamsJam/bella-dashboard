<?php

namespace App\Application\Clothes\Guard\Collection;

use App\Application\Clothes\Guard\Collection\Rules\Publish\CollectionPublishRuleInterface;
use App\Entity\Collections\Collections;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class CollectionOnlineGuard
{
    /**
     * @param iterable<CollectionPublishRuleInterface> $publishRules
     */
    public function __construct(
        #[AutowireIterator(CollectionPublishRuleInterface::class)]
        private iterable $publishRules = [],
    ) {
    }

    public function canPublish(Collections $collection): CollectionPublishValidationResult
    {
        $errors = [];

        foreach ($this->publishRules as $rule) {
            $error = $rule->validate($collection);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return new CollectionPublishValidationResult($errors === [], $errors);
    }
}
