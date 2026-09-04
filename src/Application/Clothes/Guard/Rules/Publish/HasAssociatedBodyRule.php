<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasAssociatedBodyRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        // Temporairement neutralisee : l'association a un avatar ne bloque plus
        // la mise en ligne d'un vetement.
        return null;
    }
}
