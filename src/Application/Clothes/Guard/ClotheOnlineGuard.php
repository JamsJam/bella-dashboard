<?php

namespace App\Application\Clothes\Guard;

use App\Application\Clothes\Guard\Rules\Publish\ClothePublishRuleInterface;
use App\Application\Clothes\Guard\Rules\Publish\ClotheVariantsPublishRuleInterface;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
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

    public function canPublishVariant(ClothesVariant $variant): ClothePublishValidationResult
    {
        $clothe = $variant->getClothes();

        if (!$clothe instanceof Clothes) {
            return new ClothePublishValidationResult(false, ['La variante doit être rattachée à un vêtement.']);
        }

        $slug = $variant->getSlug();
        $slugVariants = array_values(array_filter(
            $clothe->getVariants()->toArray(),
            static fn (ClothesVariant $candidate): bool => $candidate->getSlug() === $slug,
        ));
        $errors = $this->validateRules($clothe, $slugVariants);

        $variantError = $this->validateVariant($variant);

        if ($variantError !== null) {
            $errors[] = $variantError;
        }

        $errors = array_values(array_unique($errors));

        return new ClothePublishValidationResult($errors === [], $errors);
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    public function canPublishVariants(array $variants): ClothePublishValidationResult
    {
        if ($variants === []) {
            return new ClothePublishValidationResult(false, ['Au moins une variante est requise.']);
        }

        $firstVariant = $variants[0];
        $clothe = $firstVariant instanceof ClothesVariant ? $firstVariant->getClothes() : null;
        $errors = $clothe instanceof Clothes
            ? $this->validateRules($clothe, $variants)
            : ['Les variantes doivent être rattachées à un vêtement.'];

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant) {
                $errors[] = 'Une variante est invalide.';

                continue;
            }

            $variantError = $this->validateVariant($variant);

            if ($variantError !== null) {
                $errors[] = sprintf('%s : %s', $variant->getName() ?? 'Variante', $variantError);
            }
        }

        $errors = array_values(array_unique($errors));

        return new ClothePublishValidationResult($errors === [], $errors);
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<string>
     */
    private function validateRules(Clothes $clothe, array $variants): array
    {
        $errors = [];

        foreach ($this->publishRules as $rule) {
            $error = $rule instanceof ClotheVariantsPublishRuleInterface
                ? $rule->validateVariants($variants)
                : $rule->validate($clothe);

            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function validateVariant(ClothesVariant $variant): ?string
    {
        return $variant->getStock() > 0 ? null : 'La variante doit avoir du stock.';
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    public function areVariantsOnline(array $variants): bool
    {
        if ($variants === []) {
            return false;
        }

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || $variant->getPublicationStatus() !== ClotheStatus::Online) {
                return false;
            }
        }

        return true;
    }
}
