<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasSeoDescriptionRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return trim((string) $clothe->getMetadescription()) !== '' ? null : 'La meta description SEO est requise.';
    }
}
