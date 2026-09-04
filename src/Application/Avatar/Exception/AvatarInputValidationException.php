<?php

namespace App\Application\Avatar\Exception;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final class AvatarInputValidationException extends \RuntimeException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
    ) {
        parent::__construct('Avatar input validation failed.');
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }

    /** @return array<string, list<string>> */
    public function errors(): array
    {
        $errors = [];

        foreach ($this->violations as $violation) {
            $path = $violation->getPropertyPath() ?: '_global';
            $errors[$path][] = (string) $violation->getMessage();
        }

        return $errors;
    }

    public function firstError(): string
    {
        return count($this->violations) > 0
            ? (string) $this->violations->get(0)->getMessage()
            : 'Données invalides.';
    }
}
