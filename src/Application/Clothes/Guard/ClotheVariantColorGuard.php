<?php

namespace App\Application\Clothes\Guard;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;

final readonly class ClotheVariantColorGuard
{
    public function assertAvailable(
        Clothes $clothe,
        Clothescolor $newColor,
        Clothescolor $currentColor,
    ): void {
        $newColorName = mb_strtolower(trim((string) $newColor->getName()));

        foreach ($clothe->getVariants() as $existingVariant) {
            $existingColor = $existingVariant->getColor();
            if (!$existingColor instanceof Clothescolor || $existingColor === $currentColor) {
                continue;
            }

            if (
                $existingColor === $newColor
                || mb_strtolower(trim((string) $existingColor->getName())) === $newColorName
            ) {
                throw new \InvalidArgumentException(sprintf('Le vêtement possède déjà des variantes de couleur %s.', $newColor->getName()));
            }
        }
    }
}
