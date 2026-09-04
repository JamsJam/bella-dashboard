<?php

namespace App\Application\Clothes\Guard\Collection\Rules\Publish;

use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;

final readonly class HasPublishableClotheRule implements CollectionPublishRuleInterface
{
    public function __construct(
        private ClotheOnlineGuard $clotheOnlineGuard,
    ) {
    }

    public function validate(Collections $collection): ?string
    {
        foreach ($collection->getClothes() as $clothe) {
            if ($clothe instanceof Clothes && $this->clotheOnlineGuard->canPublish($clothe)->canPublish()) {
                return null;
            }
        }

        return 'La collection doit contenir au moins un vêtement publiable.';
    }
}
