<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\DTO\ClotheHighlightImageUpdateInput;
use App\Application\Clothes\Exception\ClotheNotFoundException;
use App\Application\Clothes\Persister\ClotheVariantPersister;
use App\Application\Clothes\Provider\ClotheProvider\ClotheProvider;
use App\Entity\Clothes\Clothes;

final readonly class ClotheHighlightImageUpdateService
{
    public function __construct(
        private ClotheProvider $provider,
        private ClotheImageStorageService $imageStorageService,
        private ClotheVariantPersister $variantPersister,
    ) {
    }

    public function update(ClotheHighlightImageUpdateInput $input): void
    {
        $variants = $this->provider->getClotheVariantsBySlug($input->slug);
        $clothe = $variants[0]?->getClothes();

        if (!$clothe instanceof Clothes) {
            throw new ClotheNotFoundException('Clothe not found.');
        }

        $selectedImage = trim($input->selectedImage);

        if (null !== $input->uploadedImage) {
            $storedImages = $this->imageStorageService->storeClotheImages(
                [$input->uploadedImage],
                (string) $clothe->getName(),
            );
            $selectedImage = $storedImages[0]->path ?? '';
        }

        $availableImages = [];

        foreach ($variants as $variant) {
            $availableImages = [
                ...$availableImages,
                ...($variant->getImages() ?? []),
            ];
        }

        if (
            '' === $selectedImage
            || (
                !in_array($selectedImage, $availableImages, true)
                && null === $input->uploadedImage
            )
        ) {
            throw new \InvalidArgumentException(
                'Selectionne une image valide.',
            );
        }

        $now = new \DateTimeImmutable();
        $clothe->setEditedAt($now);

        foreach ($variants as $variant) {
            if ('carousel' === $input->slot) {
                $variant
                    ->setHighlightImage($selectedImage)
                    ->setIsInCarousel(true);
            } else {
                $variant->setBestsellerImage($selectedImage);
            }

            $variant->setEditedAt($now);
        }

        $this->variantPersister->saveAll($variants);
    }
}
