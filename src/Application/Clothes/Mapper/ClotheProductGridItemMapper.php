<?php

namespace App\Application\Clothes\Mapper;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use App\UI\ProductGrid\ProductGridItemModel;

final readonly class ClotheProductGridItemMapper
{
    public function map(Clothes $clothe): ProductGridItemModel
    {
        $images = $clothe->getImages() ?? [];
        $collection = $clothe->getCollection();

        return new ProductGridItemModel(
            id: (string) $clothe->getId(),
            name: (string) $clothe->getName(),
            imageUrl: (string) ($images[0] ?? $collection?->getImage() ?? ''),
            imageUrls: array_values(array_filter($images)),
            slug: (string) $clothe->getSlug(),
            isOnline: (bool) $collection?->getCategory()?->isOnline()
                && (bool) $collection?->isOnline()
                && $clothe->hasOnlineVariant(),
            attributes: [
                'collection' => $collection?->getName(),
                'category' => $collection?->getCategory()?->getName(),
                'color' => $clothe->getColor()?->getName(),
            ],
        );
    }

    public function mapVariantGroup(
        ClothesVariant $variant,
    ): ProductGridItemModel {
        $clothe = $variant->getClothes();
        $images = $variant->getImages() ?? [];
        $groupVariants = $this->filterVariantsByColor(
            $clothe?->getVariants()->toArray() ?? [],
            $variant->getColor()?->getId(),
        );
        $stock = 0;
        $publicationStatus = $variant->getPublicationStatus();

        foreach ($groupVariants as $groupVariant) {
            $stock += $groupVariant->getStock();

            if (
                $groupVariant->getPublicationStatus()->progressionRank()
                > $publicationStatus->progressionRank()
            ) {
                $publicationStatus = $groupVariant->getPublicationStatus();
            }
        }

        return new ProductGridItemModel(
            id: (string) $variant->getId(),
            name: trim(sprintf(
                '%s %s',
                (string) $clothe?->getName(),
                (string) $variant->getColor()?->getName(),
            )),
            imageUrl: (string) (
                $images[0]
                ?? $clothe?->getCollection()?->getImage()
                ?? ''
            ),
            slug: (string) $variant->getSlug(),
            isOnline: ClotheStatus::Online === $publicationStatus,
            publicationStatus: $publicationStatus->value,
            attributes: [
                'collection' => $clothe?->getCollection()?->getName(),
                'category' => $clothe?->getCollection()?->getCategory()?->getName(),
                'sizes' => (string) count($groupVariants),
                'stock' => (string) $stock,
            ],
        );
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<ClothesVariant>
     */
    private function filterVariantsByColor(
        array $variants,
        ?int $colorId,
    ): array {
        if (null === $colorId) {
            return $variants;
        }

        return array_values(array_filter(
            $variants,
            static fn (ClothesVariant $variant): bool =>
                $variant->getColor()?->getId() === $colorId,
        ));
    }
}
