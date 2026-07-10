<?php

namespace App\Application\Clothes\Guard;

use App\Application\Clothes\Guard\Rules\Publish\ClothePublishRuleInterface;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ClotheOnlineGuard
{
    /**
     * @param iterable<ClothePublishRuleInterface> $publishRules
     */
    public function __construct(
        #[AutowireIterator(ClothePublishRuleInterface::class)]
        private iterable $publishRules = [],
    ) {
    }

    public function canPublish(Clothes $clothe): ClothePublishValidationResult
    {
        $errors = [];

        foreach ($this->publishRules as $rule) {
            $error = $rule->validate($clothe);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return new ClothePublishValidationResult($errors === [], $errors);
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    public function isOnline(array $variants): bool
    {
        if ($variants === []) {
            return false;
        }

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || !$variant->isOnline()) {
                return false;
            }
        }

        return true;
    }
}
