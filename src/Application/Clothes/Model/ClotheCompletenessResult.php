<?php

namespace App\Application\Clothes\Model;

final readonly class ClotheCompletenessResult
{
    /** @param list<string> $errors */
    public function __construct(private array $errors)
    {
    }

    public function isComplete(): bool
    {
        return [] === $this->errors;
    }

    /** @return list<string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
