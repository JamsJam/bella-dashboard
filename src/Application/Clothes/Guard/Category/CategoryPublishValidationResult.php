<?php

namespace App\Application\Clothes\Guard\Category;

final readonly class CategoryPublishValidationResult
{
    /**
     * @param list<string>                                                  $errors
     * @param list<array{label: string, isValid: bool, error: string|null}> $checks
     */
    public function __construct(
        private bool $canPublish,
        private array $errors = [],
        private array $checks = [],
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

    /**
     * @return list<array{label: string, isValid: bool, error: string|null}>
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}
