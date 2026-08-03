<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\ClothesVariant;

interface ClotheVariantsPublishRuleInterface extends ClothePublishRuleInterface
{
    /** @param list<ClothesVariant> $variants */
    public function validateVariants(array $variants): ?string;
}
