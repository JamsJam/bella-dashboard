<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasSeoDescriptionRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        foreach ($clothe->getVariants() as $variant) {
            if (trim((string) $variant->getMetadescription()) === '') {
                return 'Chaque variante doit avoir une meta description SEO.';
            }
        }

        return !$clothe->getVariants()->isEmpty() ? null : 'Au moins une variante est requise.';
    }
}
