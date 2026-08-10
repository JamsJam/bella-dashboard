<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\DTO\VariantFormInput;
use App\Application\Clothes\Persister\ClotheVariantPersister;
use App\Application\Clothes\Resolver\ClotheColorResolver;
use App\Entity\Clothes\Clothes;

final readonly class ClotheVariantCreationService
{
    public function __construct(
        private ClotheColorResolver $colorResolver,
        private ClotheImageStorageService $imageStorageService,
        private ClotheVariantFactory $variantFactory,
        private ClotheVariantPersister $variantPersister,
        private ClotheWorkflowService $workflowService,
    ) {
    }

    public function create(VariantFormInput $input): Clothes
    {
        $clothe = $input->clothe;

        if (!$clothe instanceof Clothes) {
            throw new \InvalidArgumentException('Sélectionnez un vêtement.');
        }

        $color = $this->colorResolver->resolve($input);
        $images = $this->imageStorageService->storeVariantImages(
            files: $input->images,
            clotheName: (string) $clothe->getName(),
            colorName: (string) $color->getName(),
        );
        $variants = $this->variantFactory->createGroup(
            clothe: $clothe,
            input: $input,
            color: $color,
            images: $images,
        );

        $this->variantPersister->saveAll($variants);
        $this->workflowService->reconcileCompleteness($clothe);

        return $clothe;
    }
}
