<?php

namespace App\State\Variant;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Variant\VariantStock;
use App\Entity\Clothes\ClothesVariant;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<VariantStock> */
final readonly class VariantStockProvider implements ProviderInterface
{
    public function __construct(
        private ClothesVariantRepository $variantRepository,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): VariantStock
    {
        $id = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new NotFoundHttpException('Déclinaison introuvable.');
        }

        $variant = $this->variantRepository->find($id);
        if (!$variant instanceof ClothesVariant) {
            throw new NotFoundHttpException(sprintf('La déclinaison %d est introuvable.', $id));
        }

        $stock = $variant->getStock();

        return new VariantStock(
            variantId: $id,
            stock: $stock,
            available: $variant->getPublicationStatus() === \App\Enum\ClotheStatus::Online && $stock > 0,
        );
    }
}
