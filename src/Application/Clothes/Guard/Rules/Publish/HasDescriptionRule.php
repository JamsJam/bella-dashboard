<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasDescriptionRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return trim((string) $clothe->getDescription()) !== '' ? null : 'La description est requise.';
    }
}
