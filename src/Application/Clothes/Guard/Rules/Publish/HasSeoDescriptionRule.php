<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;

final readonly class HasSeoDescriptionRule implements ClotheVariantsPublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return $this->validateVariants($clothe->getVariants()->toArray());
    }

    /** @param list<ClothesVariant> $variants */
    public function validateVariants(array $variants): ?string
    {
        foreach ($variants as $variant) {
            if ('' === trim((string) $variant->getMetadescription())) {
                return 'Chaque variante doit avoir une meta description SEO.';
            }
        }

        return [] !== $variants ? null : 'Au moins une variante est requise.';
    }
}
