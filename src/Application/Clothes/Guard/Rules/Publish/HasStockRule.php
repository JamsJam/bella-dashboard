<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasStockRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return $clothe->getTotalStock() > 0 ? null : 'Au moins une variante doit avoir du stock.';
    }
}
