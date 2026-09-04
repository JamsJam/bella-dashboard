<?php

namespace App\State\Search;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Search\VariantCardDTO;
use App\Entity\Category\Category;
use App\Entity\Clothes\ClothesVariant;
use App\Repository\Category\CategoryRepository;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<VariantCardDTO>
 */
final readonly class VariantSearchProvider implements ProviderInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ClothesVariantRepository $variantRepository,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return list<VariantCardDTO>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $categorySlug = $uriVariables['category'] ?? null;
        if (!is_string($categorySlug) || 1 !== preg_match('/^[a-zA-Z0-9_-]+$/', $categorySlug)) {
            throw new NotFoundHttpException('Catégorie introuvable.');
        }

        $category = $this->categoryRepository->findOneBy(['slug' => $categorySlug, 'isOnline' => true]);
        if (!$category instanceof Category || null === $category->getId()) {
            throw new NotFoundHttpException(sprintf('La catégorie "%s" est introuvable.', $categorySlug));
        }

        $request = $this->requestStack->getCurrentRequest();
        $colors = $this->stringList($request?->query->all('color') ?? [], 'color');
        $sizes = $this->stringList($request?->query->all('size') ?? [], 'size');
        [$minimumPrice, $maximumPrice] = $this->priceRange($request?->query->all('price') ?? []);

        $variants = $this->variantRepository->searchOnlineByCategory(
            categoryId: $category->getId(),
            colors: $colors,
            sizes: $sizes,
            minimumPrice: $minimumPrice,
            maximumPrice: $maximumPrice,
        );

        $groups = array_values($this->groupBySlug($variants));

        return array_map(
            fn (array $group): VariantCardDTO => $this->mapVariantGroup($group),
            $groups,
        );
    }

    /**
     * @param non-empty-list<ClothesVariant> $group
     */
    private function mapVariantGroup(array $group): VariantCardDTO
    {
        $variant = $group[0];
        $availableGroup = [];
        $imagePaths = [];
        $colors = [];
        $sizes = [];

        foreach ($variant->getClothes()?->getVariants() ?? [] as $availableVariant) {
            if (
                $availableVariant->getSlug() === $variant->getSlug()
                && \App\Enum\ClotheStatus::Online === $availableVariant->getPublicationStatus()
                && $availableVariant->getStock() > 0
            ) {
                $availableGroup[] = $availableVariant;
            }
        }

        foreach ([] !== $availableGroup ? $availableGroup : $group as $groupVariant) {
            foreach ($groupVariant->getImages() ?? [] as $path) {
                if (is_string($path) && '' !== $path) {
                    $imagePaths[$path] = $path;
                }
            }

            $color = $groupVariant->getColor()?->getName();
            if (is_string($color) && '' !== $color) {
                $colors[$color] = $color;
            }

            $size = $groupVariant->getSize()?->getName();
            if (is_string($size) && '' !== $size) {
                $sizes[$size] = $size;
            }
        }

        $images = $this->absoluteUrls(array_values($imagePaths));

        return new VariantCardDTO(
            name: $variant->getDisplayName(),
            slug: (string) $variant->getSlug(),
            price: (int) $variant->getClothes()?->getPrice(),
            image: $images[0] ?? null,
            images: $images,
            colors: array_values($colors),
            sizes: array_values($sizes),
        );
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return list<non-empty-list<ClothesVariant>>
     */
    private function groupBySlug(array $variants): array
    {
        $groups = [];

        foreach ($variants as $variant) {
            $slug = $variant->getSlug();
            if (null !== $slug && '' !== $slug) {
                $groups[$slug][] = $variant;
            }
        }

        return array_values($groups);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return list<string>
     */
    private function stringList(array $values, string $parameter): array
    {
        $normalized = [];

        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new BadRequestHttpException(sprintf('Le filtre "%s" est invalide.', $parameter));
            }

            $value = trim($value);
            if ('' !== $value) {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array{int|null, int|null}
     */
    private function priceRange(array $values): array
    {
        if (count($values) > 2) {
            throw new BadRequestHttpException('Le filtre "price" accepte au maximum un prix minimum et un prix maximum.');
        }

        $prices = [];
        foreach ($values as $value) {
            if (!is_scalar($value) || false === filter_var($value, FILTER_VALIDATE_INT) || (int) $value < 0) {
                throw new BadRequestHttpException('Les prix doivent être des nombres entiers positifs exprimés en centimes.');
            }

            $prices[] = (int) $value;
        }

        $minimum = $prices[0] ?? null;
        $maximum = $prices[1] ?? null;
        if (null !== $minimum && null !== $maximum && $minimum > $maximum) {
            throw new BadRequestHttpException('Le prix minimum ne peut pas être supérieur au prix maximum.');
        }

        return [$minimum, $maximum];
    }

    /**
     * @param array<array-key, mixed> $paths
     *
     * @return list<string>
     */
    private function absoluteUrls(array $paths): array
    {
        $urls = [];
        $request = $this->requestStack->getCurrentRequest();

        foreach ($paths as $path) {
            if (!is_string($path) || '' === $path) {
                continue;
            }

            $urls[] = 1 === preg_match('#^https?://#i', $path) || str_starts_with($path, '//') || null === $request
                ? $path
                : $request->getSchemeAndHttpHost() . '/' . ltrim($path, '/');
        }

        return $urls;
    }
}
