<?php

namespace App\Application\Clothes\Guard\Category\Rules\Publish;

use App\Entity\Category\Category;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::class)]
interface CategoryPublishRuleInterface
{
    public function validate(Category $category): ?string;
}
