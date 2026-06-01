<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasTitleRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return trim((string) $clothe->getName()) !== '' ? null : 'Le titre du vetement est requis.';
    }
}
