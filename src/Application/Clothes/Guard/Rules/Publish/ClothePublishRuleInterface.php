<?php

namespace App\Application\Clothes\Guard\Rules\Publish;

use App\Entity\Clothes\Clothes;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::class)]
interface ClothePublishRuleInterface
{
    public function validate(Clothes $clothe): ?string;
}
