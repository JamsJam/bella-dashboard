<?php

namespace App\Validator;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DtoValidator
{
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Valide un DTO en utilisant les contraintes définies via @Assert.
     *
     * @throws BadRequestHttpException si des violations sont détectées
     */
    public function validate(object $dto): void
    {
        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $messages = [];

            foreach ($violations as $violation) {
                $messages[] = sprintf(
                    '%s: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                );
            }

            throw new BadRequestHttpException(implode("\n", $messages));
        }
    }
}
