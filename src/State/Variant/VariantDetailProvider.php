<?php

namespace App\State\Variant;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Variant\ClothesVariantItemDTO;
use App\ApiResource\Variant\ClothesVariantsDTO;
use App\ApiResource\Variant\RelatedVariantDTO;
use App\ApiResource\Variant\SizeGuideDTO;
use App\ApiResource\Variant\SizeGuideMeasurementDTO;
use App\ApiResource\Variant\SizeGuideSizeDTO;
use App\ApiResource\Variant\VariantCategoryDTO;
use App\ApiResource\Variant\VariantColorDTO;
use App\ApiResource\Variant\VariantDetailDTO;
use App\ApiResource\Variant\VariantReviewDTO;
use App\ApiResource\Variant\VariantSizeDTO;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Reviews\Review;
use App\Entity\SizeGuide;
use App\Repository\Clothes\ClothesVariantRepository;
use App\Repository\Reviews\ReviewRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<VariantDetailDTO> */
final readonly class VariantDetailProvider implements ProviderInterface
{
    public function __construct(
        private ClothesVariantRepository $variantRepository,
        private ReviewRepository $reviewRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): VariantDetailDTO
    {
        $slug = $uriVariables['slug'] ?? null;
        if (!is_string($slug) || 1 !== preg_match('/^[a-zA-Z0-9_-]+$/', $slug)) {
            throw new NotFoundHttpException('Déclinaison introuvable.');
        }

        $variants = $this->variantRepository->findOnlineBySlug($slug);
        if ([] === $variants) {
            throw new NotFoundHttpException(sprintf('La déclinaison "%s" est introuvable.', $slug));
        }

        $variant = $variants[0];
        $clothes = $variant->getClothes();
        $collection = $clothes?->getCollection();
        $category = $collection?->getCategory();
        if (null === $category) {
            throw new NotFoundHttpException(sprintf('La catégorie de la déclinaison "%s" est introuvable.', $slug));
        }

        $collectionId = $collection?->getId();
        $collectionVariants = null === $collectionId
            ? $variants
            : $this->publishableVariants($this->variantRepository->findOnlineByCollection($collectionId));
        $images = $this->images($variants);

        return new VariantDetailDTO(
            name: $variant->getDisplayName(),
            slug: $slug,
            price: (int) $clothes?->getPrice(),
            category: new VariantCategoryDTO(
                name: (string) $category->getName(),
                slug: (string) $category->getSlug(),
            ),
            clothesVariant: new ClothesVariantsDTO(
                name: (string) $clothes?->getName(),
                variants: $this->clothesVariants($collectionVariants, $clothes?->getId()),
            ),
            description: $variant->getDescription(),
            metadescription: $variant->getMetadescription(),
            image: $images[0] ?? null,
            images: $images,
            sizes: $this->sizes($variants),
            sizeGuide: $this->sizeGuide($variant->getSizeGuide()),
            colors: $this->colors($collectionVariants, $clothes?->getId()),
            relatedProducts: $this->relatedProducts($collectionVariants, $slug),
            reviews: $clothes instanceof Clothes ? $this->reviews($clothes) : [],
        );
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<ClothesVariant>
     */
    private function publishableVariants(array $variants): array
    {
        $groups = [];
        foreach ($variants as $variant) {
            $slug = $variant->getSlug();
            if (is_string($slug) && '' !== $slug) {
                $groups[$slug][] = $variant;
            }
        }

        $publishable = [];
        foreach ($groups as $group) {
            array_push($publishable, ...$group);
        }

        return $publishable;
    }

    /** @param list<ClothesVariant> $variants
     * @return list<string>
     */
    private function images(array $variants): array
    {
        $images = [];
        foreach ($variants as $variant) {
            foreach ($variant->getImages() ?? [] as $path) {
                if (is_string($path) && '' !== $path) {
                    $images[$path] = $this->absoluteUrl($path);
                }
            }
        }

        return array_values($images);
    }

    /** @param list<ClothesVariant> $variants
     * @return list<VariantSizeDTO>
     */
    private function sizes(array $variants): array
    {
        $sizes = [];
        foreach ($variants as $variant) {
            $size = $variant->getSize()?->getName();
            $variantId = $variant->getId();
            if (is_string($size) && '' !== $size && null !== $variantId) {
                $sizes[$size] = new VariantSizeDTO(
                    id: $variantId,
                    name: $size,
                );
            }
        }

        return array_values($sizes);
    }

    /**
     * @param list<ClothesVariant> $variants
     *
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
            if (!is_string($name) || '' === $name || !is_string($slug) || '' === $slug) {
                continue;
            }

            $key = mb_strtolower($name);
            $colors[$key] ??= new VariantColorDTO($name, $slug, $variant->getColor()?->getHexa());
        }

        return array_values($colors);
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<ClothesVariantItemDTO>
     */
    private function clothesVariants(array $variants, ?int $clothesId): array
    {
        $items = [];
        foreach ($variants as $variant) {
            if ($variant->getClothes()?->getId() !== $clothesId) {
                continue;
            }

            $slug = $variant->getSlug();
            $name = $variant->getName();
            $color = $variant->getColor()?->getName();
            if (
                !is_string($slug) || '' === $slug
                || !is_string($name) || '' === $name
                || !is_string($color) || '' === $color
            ) {
                continue;
            }

            $items[$slug] ??= new ClothesVariantItemDTO(
                slug: $slug,
                name: $name,
                color: $color,
                hexa: $variant->getColor()?->getHexa(),
            );
        }

        return array_values($items);
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<RelatedVariantDTO>
     */
    private function relatedProducts(array $variants, string $currentSlug): array
    {
        $groups = [];
        foreach ($variants as $variant) {
            $slug = $variant->getSlug();
            if (!is_string($slug) || '' === $slug || $slug === $currentSlug) {
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
        if (null === $guide) {
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
                if (null === $type) {
                    continue;
                }
                $measurementDTOs[] = new SizeGuideMeasurementDTO(
                    uuid: $type->getUuid()->toRfc4122(),
                    label: (string) $type->getLabel(),
                    value: (string) $measurement->getValue(),
                    unit: (string) $measurement->getUnit(),
                );
            }

            $sizeDTOs[] = new SizeGuideSizeDTO((string) $size->getLabel(), $measurementDTOs);
        }

        return new SizeGuideDTO((string) $guide->getUnit(), $sizeDTOs);
    }

    /** @return list<VariantReviewDTO> */
    private function reviews(Clothes $clothes): array
    {
        return array_map(
            static fn (Review $review): VariantReviewDTO => new VariantReviewDTO(
                rating: (int) $review->getRating(),
                comment: (string) $review->getComment(),
                createdAt: $review->getCreatedAt()->format(\DateTimeInterface::ATOM),
                reply: $review->getReply(),
                repliedAt: $review->getReplyAt()?->format(\DateTimeInterface::ATOM),
            ),
            $this->reviewRepository->findAcceptedByClothes($clothes),
        );
    }

    private function absoluteUrl(string $path): string
    {
        if (1 === preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (str_starts_with($path, '//')) {
            return ($request?->getScheme() ?? 'https') . ':' . $path;
        }

        return null === $request ? $path : $request->getSchemeAndHttpHost() . '/' . ltrim($path, '/');
    }
}
