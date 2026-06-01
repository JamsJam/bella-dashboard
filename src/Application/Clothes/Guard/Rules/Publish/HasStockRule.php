<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasStockRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return ($clothe->getStock() ?? 0) > 0 ? null : 'Le stock doit etre renseigne et superieur a 0.';
    }
}
