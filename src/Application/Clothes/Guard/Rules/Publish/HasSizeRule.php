<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasSizeRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        foreach ($clothe->getVariants() as $variant) {
            if ($variant->getSize() !== null) {
                return null;
            }
        }

        return 'Au moins une taille de variante est requise.';
    }
}
