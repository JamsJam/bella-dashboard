<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\DTO\ClotheImageInput;
use App\Application\Clothes\DTO\ClotheImagesUpdateInput;
use App\Application\Clothes\Exception\ClotheNotFoundException;
use App\Application\Clothes\Persister\ClotheVariantPersister;
use App\Application\Clothes\Provider\ClotheProvider\ClotheProvider;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;

final readonly class ClotheImagesUpdateService
{
    public function __construct(
        private ClotheProvider $provider,
        private ClotheImageStorageService $imageStorageService,
        private ClotheVariantPersister $variantPersister,
    ) {
    }

    public function update(ClotheImagesUpdateInput $input): void
    {
        $variants = $this->provider->getClotheVariantsBySlug($input->slug);
        $clothe = $this->resolveClothe($variants);
        $imageVariants = $this->filterVariantsByColor(
            $variants,
            $input->colorId,
        );
        $availableImages = $this->collectImages($imageVariants);
        $keptImages = array_values(array_unique(array_filter(
            $input->keptImages,
            static fn (mixed $image): bool => is_string($image)
                && in_array($image, $availableImages, true),
        )));
        $storedImages = $this->imageStorageService->storeClotheImages(
            $input->uploadedImages,
            (string) $clothe->getName(),
        );
        $images = [
            ...$keptImages,
            ...array_map(
                static fn (ClotheImageInput $image): string => $image->path,
                $storedImages,
            ),
        ];

        if ([] === $images) {
            throw new \InvalidArgumentException(
                'Conserve ou ajoute au moins une image.',
            );
        }

        $now = new \DateTimeImmutable();
        $clothe->setEditedAt($now);

        foreach ($imageVariants as $variant) {
            $variant
                ->setImages($images)
                ->setEditedAt($now);
        }

        $this->variantPersister->saveAll($imageVariants);
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function resolveClothe(array $variants): Clothes
    {
        $clothe = $variants[0]?->getClothes();

        if (!$clothe instanceof Clothes) {
            throw new ClotheNotFoundException('Clothe not found.');
        }

        return $clothe;
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

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<string>
     */
    private function collectImages(array $variants): array
    {
        $images = [];

        foreach ($variants as $variant) {
            $images = [...$images, ...($variant->getImages() ?? [])];
        }

        return array_values(array_unique(array_filter($images)));
    }
}
