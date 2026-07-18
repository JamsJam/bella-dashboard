<?php

namespace App\State\Variant;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Variant\RelatedVariantDTO;
use App\ApiResource\Variant\SizeGuideDTO;
use App\ApiResource\Variant\SizeGuideMeasurementDTO;
use App\ApiResource\Variant\SizeGuideSizeDTO;
use App\ApiResource\Variant\VariantColorDTO;
use App\ApiResource\Variant\VariantDetailDTO;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\SizeGuide;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<VariantDetailDTO> */
final readonly class VariantDetailProvider implements ProviderInterface
{
    public function __construct(
        private ClothesVariantRepository $variantRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): VariantDetailDTO
    {
        $slug = $uriVariables['slug'] ?? null;
        if (!is_string($slug) || preg_match('/^[a-zA-Z0-9_-]+$/', $slug) !== 1) {
            throw new NotFoundHttpException('Déclinaison introuvable.');
        }

        $variants = $this->variantRepository->findOnlineBySlug($slug);
        if ($variants === []) {
            throw new NotFoundHttpException(sprintf('La déclinaison "%s" est introuvable.', $slug));
        }

        $variant = $variants[0];
        $clothes = $variant->getClothes();
        $collectionId = $clothes?->getCollection()?->getId();
        $collectionVariants = $collectionId === null
            ? $variants
            : $this->variantRepository->findOnlineByCollection($collectionId);
        $images = $this->images($variants);

        return new VariantDetailDTO(
            name: $variant->getDisplayName(),
            slug: $slug,
            price: (int) $clothes?->getPrice(),
            description: $variant->getDescription(),
            metadescription: $variant->getMetadescription(),
            image: $images[0] ?? null,
            images: $images,
            sizes: $this->sizes($variants),
            sizeGuide: $this->sizeGuide($variant->getSizeGuide()),
            colors: $this->colors($collectionVariants, $clothes?->getId()),
            relatedProducts: $this->relatedProducts($collectionVariants, $slug),
        );
    }

    /** @param list<ClothesVariant> $variants
     *  @return list<string>
     */
    private function images(array $variants): array
    {
        $images = [];
        foreach ($variants as $variant) {
            foreach ($variant->getImages() ?? [] as $path) {
                if (is_string($path) && $path !== '') {
                    $images[$path] = $this->absoluteUrl($path);
                }
            }
        }

        return array_values($images);
    }

    /** @param list<ClothesVariant> $variants
     *  @return list<string>
     */
    private function sizes(array $variants): array
    {
        $sizes = [];
        foreach ($variants as $variant) {
            $size = $variant->getSize()?->getName();
            if (is_string($size) && $size !== '') {
                $sizes[$size] = $size;
            }
        }

        return array_values($sizes);
    }

    /**
     * @param list<ClothesVariant> $variants
     * @return list<VariantColorDTO>
     */
    private function colors(array $variants, ?int $clothesId): array
    {
        $colors = [];
        foreach ($variants as $variant) {
            if ($variant->getClothes()?->getId() !== $clothesId) {
                continue;
            }

            $name = $variant->getColor()?->getName();
            $slug = $variant->getSlug();
            if (!is_string($name) || $name === '' || !is_string($slug) || $slug === '') {
                continue;
            }

            $key = mb_strtolower($name);
            $colors[$key] ??= new VariantColorDTO($name, $slug, $variant->getColor()?->getHexa());
        }

        return array_values($colors);
    }

    /**
     * @param list<ClothesVariant> $variants
     * @return list<RelatedVariantDTO>
     */
    private function relatedProducts(array $variants, string $currentSlug): array
    {
        $groups = [];
        foreach ($variants as $variant) {
            $slug = $variant->getSlug();
            if (!is_string($slug) || $slug === '' || $slug === $currentSlug) {
                continue;
            }
            $groups[$slug][] = $variant;
        }

        $products = [];
        foreach ($groups as $slug => $group) {
            $images = $this->images($group);
            $products[] = new RelatedVariantDTO(
                name: $group[0]->getDisplayName(),
                slug: $slug,
                image: $images[0] ?? null,
                images: $images,
            );
        }

        return $products;
    }

    private function sizeGuide(?SizeGuide $guide): ?SizeGuideDTO
    {
        if ($guide === null) {
            return null;
        }

        $sizes = $guide->getSizes()->toArray();
        usort($sizes, static fn ($left, $right): int => ($left->getPosition() ?? 0) <=> ($right->getPosition() ?? 0));

        $sizeDTOs = [];
        foreach ($sizes as $size) {
            $measurements = $size->getMeasurements()->toArray();
            usort($measurements, static fn ($left, $right): int => ($left->getType()?->getPosition() ?? 0) <=> ($right->getType()?->getPosition() ?? 0));

            $measurementDTOs = [];
            foreach ($measurements as $measurement) {
                $type = $measurement->getType();
                if ($type === null) {
                    continue;
                }
                $measurementDTOs[] = new SizeGuideMeasurementDTO(
                    code: (string) $type->getCode(),
                    label: (string) $type->getLabel(),
                    value: (string) $measurement->getValue(),
                    unit: (string) $measurement->getUnit(),
                );
            }

            $sizeDTOs[] = new SizeGuideSizeDTO((string) $size->getLabel(), $measurementDTOs);
        }

        return new SizeGuideDTO((string) $guide->getUnit(), $sizeDTOs);
    }

    private function absoluteUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, '//')) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();

        return $request === null ? $path : $request->getSchemeAndHttpHost().'/'.ltrim($path, '/');
    }
}
