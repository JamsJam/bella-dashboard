<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;

final readonly class HasImagesRule implements ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string
    {
        $images = array_values(array_filter($clothe->getImages() ?? []));

        return $images !== [] ? null : 'Au moins une image est requise.';
    }
}
