<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\Guard\ClotheNameGuard;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class ClotheRenameService
{
    public function __construct(
        private ClotheNameGuard $clotheNameGuard,
    ) {
    }

    public function renameClothe(Clothes $clothe, string $currentSlug, string $newName): string
    {
        $name = $this->clotheNameGuard->assertNameAvailable($newName, $currentSlug);
        $slug = $this->clotheNameGuard->createSlug($name);
        $now = new \DateTimeImmutable();
        $firstVariantSlug = null;

        $clothe
            ->setName($name)
            ->setEditedAt($now);

        foreach ($clothe->getVariants() as $variant) {
            if (!$variant instanceof ClothesVariant) {
                continue;
            }

            $variantName = $this->createVariantName($name, $variant);
            $variantSlug = $this->createVariantSlug($name, $variant);
            $firstVariantSlug ??= $variantSlug;

            $variant
                ->setName($variantName)
                ->setSlug($variantSlug)
                ->setEditedAt($now);
        }

        return $firstVariantSlug ?? $slug;
    }

    /**
     * @param list<mixed> $variants
     */
    public function renameVariants(array $variants, string $currentSlug, string $newName): string
    {
        $firstVariant = $variants[0] ?? null;
        if (is_object($firstVariant) && method_exists($firstVariant, 'getClothes')) {
            $clothe = $firstVariant->getClothes();
            if ($clothe instanceof Clothes) {
                return $this->renameClothe($clothe, $currentSlug, $newName);
            }
        }

        throw new \InvalidArgumentException('Clothe not found.');
    }

    private function createVariantName(string $name, ClothesVariant $variant): string
    {
        return trim(sprintf(
            '%s %s %s',
            $name,
            (string) $variant->getColor()?->getName(),
            (string) $variant->getSize()?->getName(),
        ));
    }

    private function createVariantSlug(string $name, ClothesVariant $variant): string
    {
        return strtolower((string) (new AsciiSlugger())->slug(trim(sprintf(
            '%s %s',
            $name,
            (string) $variant->getColor()?->getName(),
        ))));
    }
}
