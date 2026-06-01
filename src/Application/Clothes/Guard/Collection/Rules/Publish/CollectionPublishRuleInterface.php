<?php

namespace App\Application\Clothes\Guard\Collection\Rules\Publish;

use App\Entity\Collections\Collections;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::class)]
interface CollectionPublishRuleInterface
{
    public function validate(Collections $collection): ?string;
}
