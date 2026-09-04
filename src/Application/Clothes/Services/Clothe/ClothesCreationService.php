<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\DTO\ClotheFormInput;
use App\Application\Clothes\Factory\ClotheFactory;
use App\Application\Clothes\Guard\ClotheNameGuard;
use App\Application\Clothes\Persister\ClotheVariantPersister;
use App\Application\Clothes\Persister\ClothesPersister;
use App\Application\Clothes\Resolver\ClotheColorResolver;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use Symfony\Component\HttpFoundation\Request;

final class ClothesCreationService
{
    public function __construct(
        private readonly ClothesPersister $clothesPersister,
        private readonly ClotheVariantPersister $variantPersister,
        private readonly ClotheFactory $clotheFactory,
        private readonly ClotheNameGuard $clotheNameGuard,
        private readonly ClotheColorResolver $colorResolver,
        private readonly ClotheImageStorageService $imageStorageService,
        private readonly ClotheVariantFactory $variantFactory,
        private readonly ClotheWorkflowService $workflowService,
    ) {
    }

    public function create(ClotheFormInput $input): Clothes
    {
        $name = $this->clotheNameGuard->assertNameAvailable(
            (string) $input->name,
        );
        $clothe = $this->clotheFactory->create(
            name: $name,
            price: $input->price,
            collection: $input->collection,
        );
        $variants = [];

        foreach ($input->variants as $variantInput) {
            $color = $this->colorResolver->resolve($variantInput);
            $images = $this->imageStorageService->storeVariantImages(
                files: $variantInput->images,
                clotheName: $name,
                colorName: (string) $color->getName(),
            );
            $variants = [
                ...$variants,
                ...$this->variantFactory->createGroup(
                    clothe: $clothe,
                    input: $variantInput,
                    color: $color,
                    images: $images,
                ),
            ];
        }

        $this->clothesPersister->persist($clothe);
        $this->variantPersister->saveAll($variants);
        $this->workflowService->reconcileCompleteness($clothe);

        return $clothe;
    }

    public function createForCollectionFromRequest(Request $request, Collections $collection): void
    {
        $clothes = $request->request->all('clothes');
        if (!is_array($clothes) || [] === $clothes) {
            return;
        }

        $enabledClothes = [];
        foreach ($clothes as $index => $data) {
            if (!is_array($data) || ($data['enabled'] ?? '0') !== '1') {
                continue;
            }

            $uploadedImages = $request->files->all('clotheImages_' . $index);
            $enabledClothes[] = [
                'data' => $data,
                'images' => is_array($uploadedImages) ? $uploadedImages : [],
            ];
        }

        if ([] === $enabledClothes) {
            return;
        }

        $this->clothesPersister->createForCollection($collection, $enabledClothes);
    }
}
