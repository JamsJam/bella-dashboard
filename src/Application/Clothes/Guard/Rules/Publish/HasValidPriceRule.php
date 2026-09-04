<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasValidPriceRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return ($clothe->getPrice() ?? 0) > 0 ? null : 'Le prix doit etre renseigne et superieur a 0.';
    }
}
