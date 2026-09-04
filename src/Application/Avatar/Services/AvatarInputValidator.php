<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Exception\AvatarInputValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class AvatarInputValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /** @throws AvatarInputValidationException */
    public function validate(object $input): void
    {
        $violations = $this->validator->validate($input);

        if (count($violations) > 0) {
            throw new AvatarInputValidationException($violations);
        }
    }
}
