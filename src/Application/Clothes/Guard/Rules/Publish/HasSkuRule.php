<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasSkuRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        foreach ($clothe->getVariants() as $variant) {
            if ('' === trim((string) $variant->getSku())) {
                return 'Chaque variante doit avoir un SKU.';
            }
        }

        return !$clothe->getVariants()->isEmpty() ? null : 'Au moins une variante est requise.';
    }
}
