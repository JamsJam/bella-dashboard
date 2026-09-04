<?php

namespace App\Application\Clothes\Mapper;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;

final readonly class ClotheDetailsMapper
{
    /**
     * @param list<ClothesVariant> $variants
     * @param array<string, mixed>  $sizeGuide
     *
     * @return array<string, mixed>
     */
    public function map(
        Clothes $clothe,
        array $variants,
        array $sizeGuide,
    ): array
    {
        $images = [];
        $sizes = [];
        $colors = [];
        $variantGroups = [];
        $highlightImage = null;
        $bestsellerImage = null;
        $metadescription = null;
        $publicationStatus = null;

        foreach ($variants as $variant) {
            $sizeName = $variant->getSize()?->getName() ?? 'Taille inconnue';
            $colorName = $variant->getColor()?->getName() ?? 'Couleur inconnue';
            $colors[$colorName] = $colorName;
            $images = array_merge($images, $variant->getImages() ?? []);
            $highlightImage ??= $variant->getHighlightImage();
            $bestsellerImage ??= $variant->getBestsellerImage();
            $metadescription ??= $variant->getMetadescription();

            if (
                !$publicationStatus instanceof ClotheStatus
                || $variant->getPublicationStatus()->progressionRank()
                > $publicationStatus->progressionRank()
            ) {
                $publicationStatus = $variant->getPublicationStatus();
            }

            $size = $this->mapSize($clothe, $variant, $sizeName, $colorName);
            $sizes[] = $size;
            $groupKey = (string) (
                $variant->getColor()?->getId()
                ?? $colorName
            );

            if (!isset($variantGroups[$groupKey])) {
                $variantGroups[$groupKey] = $this->createVariantGroup(
                    $variant,
                    $colorName,
                    $groupKey,
                );
            }

            $variantGroups[$groupKey]['sizes'][] = $size;
            $variantGroups[$groupKey]['variantIds'][] = $variant->getId();
            $variantGroups[$groupKey]['images'] = [
                ...$variantGroups[$groupKey]['images'],
                ...($variant->getImages() ?? []),
            ];
            $variantGroups[$groupKey]['highlightImage'] ??=
                $variant->getHighlightImage();
            $variantGroups[$groupKey]['bestsellerImage'] ??=
                $variant->getBestsellerImage();
            $variantGroups[$groupKey]['isBestseller'] =
                $variantGroups[$groupKey]['isBestseller']
                || $variant->isBestseller();
            $variantGroups[$groupKey]['isInCarousel'] =
                $variantGroups[$groupKey]['isInCarousel']
                || $variant->isInCarousel();
        }

        foreach ($variantGroups as &$group) {
            $group['images'] = array_values(array_unique(array_filter(
                $group['images'],
            )));
            $group['variantIds'] = array_values(array_unique(array_filter(
                $group['variantIds'],
            )));
        }
        unset($group);

        return [
            'id' => $clothe->getId(),
            'name' => $clothe->getName(),
            'slug' => $variants[0]?->getSlug() ?? $clothe->getSlug(),
            'description' => $clothe->getDescription(),
            'metadescription' => $metadescription,
            'collection' => $clothe->getCollection()?->getName(),
            'collectionId' => $clothe->getCollection()?->getId(),
            'category' => $clothe->getCollection()?->getCategory()?->getName(),
            'categoryId' => $clothe->getCollection()?->getCategory()?->getId(),
            'color' => implode(', ', array_values($colors)),
            'price' => $clothe->getPrice(),
            'isOnline' => $clothe->hasOnlineVariant(),
            'publicationStatus' => $publicationStatus,
            'isBestseller' => (bool) $clothe->isBestseller(),
            'isInCarousel' => (bool) $clothe->isInCarousel(),
            'totalStock' => $clothe->getTotalStock(),
            'hasAvailableVariant' => $clothe->hasAvailableVariant(),
            'highlightImage' => $highlightImage,
            'bestsellerImage' => $bestsellerImage,
            'images' => array_values(array_unique(array_filter($images))),
            'sizes' => $sizes,
            'variantGroups' => array_values($variantGroups),
            'sizeGuide' => $sizeGuide,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSize(
        Clothes $clothe,
        ClothesVariant $variant,
        string $sizeName,
        string $colorName,
    ): array {
        return [
            'id' => $variant->getId(),
            'name' => $sizeName,
            'color' => $colorName,
            'sku' => $variant->getSku(),
            'description' => $variant->getDescription()
                ?? $clothe->getDescription(),
            'metadescription' => $variant->getMetadescription(),
            'images' => $variant->getImages() ?? [],
            'highlightImage' => $variant->getHighlightImage(),
            'bestsellerImage' => $variant->getBestsellerImage(),
            'stock' => $variant->getStock(),
            'isOnline' => ClotheStatus::Online
                === $variant->getPublicationStatus(),
            'isAvailable' => $variant->isAvailable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createVariantGroup(
        ClothesVariant $variant,
        string $colorName,
        string $groupKey,
    ): array {
        return [
            'colorId' => $variant->getColor()?->getId(),
            'color' => $colorName,
            'slug' => $variant->getSlug(),
            'sizes' => [],
            'variantIds' => [],
            'images' => [],
            'highlightImage' => null,
            'bestsellerImage' => null,
            'isBestseller' => false,
            'isInCarousel' => false,
            'toggleIdSuffix' => '-variant-'.preg_replace(
                '/[^a-zA-Z0-9_-]/',
                '-',
                $groupKey,
            ),
        ];
    }
}
