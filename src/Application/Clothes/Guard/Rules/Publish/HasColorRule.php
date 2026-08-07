<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasColorRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        foreach ($clothe->getVariants() as $variant) {
            if (null !== $variant->getColor()) {
                return null;
            }
        }

        return 'Au moins une couleur de variante est requise.';
    }
}
