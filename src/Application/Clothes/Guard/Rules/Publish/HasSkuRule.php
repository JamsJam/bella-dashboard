<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasSkuRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return trim((string) $clothe->getSku()) !== '' ? null : 'Le SKU est requis.';
    }
}
