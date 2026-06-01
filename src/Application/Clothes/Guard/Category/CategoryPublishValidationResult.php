<?php

namespace App\Application\Clothes\Guard\Category;

final readonly class CategoryPublishValidationResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(
        private bool $canPublish,
        private array $errors = [],
    ) {
    }

    public function canPublish(): bool
    {
        return $this->canPublish;
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
