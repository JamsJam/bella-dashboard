<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasAssociatedBodyRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        return !$clothe->getBodies()->isEmpty()
            ? null
            : 'Au moins un avatar doit porter ce vetement.';
    }
}
