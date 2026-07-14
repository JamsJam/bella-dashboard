<?php

namespace App\Application\Clothes\Guard\Category;

use App\Application\Clothes\Guard\Category\Rules\Publish\CategoryPublishRuleInterface;
use App\Entity\Category\Category;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class CategoryOnlineGuard
{
    /**
     * @param iterable<CategoryPublishRuleInterface> $publishRules
     */
    public function __construct(
        #[AutowireIterator(CategoryPublishRuleInterface::class)]
        private iterable $publishRules = [],
    ) {
    }

    public function canPublish(Category $category): CategoryPublishValidationResult
    {
        $errors = [];
        $checks = [];

        foreach ($this->publishRules as $rule) {
            $error = $rule->validate($category);
            $checks[] = [
                'label' => $rule->getLabel(),
                'isValid' => $error === null,
                'error' => $error,
            ];

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return new CategoryPublishValidationResult($errors === [], $errors, $checks);
    }
}
