<?php

namespace App\State\Category;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Category\CategoryListDTO;
use App\Entity\Category\Category;
use App\Entity\Clothes\ClothesVariant;
use App\Repository\Category\CategoryRepository;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<CategoryListDTO>
 */
final readonly class CategoryListProvider implements ProviderInterface
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ClothesVariantRepository $variantRepository,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return list<CategoryListDTO>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $categorySlug = $uriVariables['category'] ?? null;

        if (!is_string($categorySlug) || !preg_match('/^[a-zA-Z0-9_-]+$/', $categorySlug)) {
            throw new NotFoundHttpException('Catégorie introuvable.');
        }

        $category = $this->categoryRepository->findOneBy(['slug' => $categorySlug, 'isOnline' => true]);

        if (!$category instanceof Category || $category->getId() === null) {
            throw new NotFoundHttpException(sprintf('La catégorie "%s" est introuvable.', $categorySlug));
        }

        $groups = array_values($this->groupBySlug($this->variantRepository->findOnlineByCategory($category->getId())));

        return array_map(
            function (array $group): CategoryListDTO {
                $variant = $group[0];
                $images = $this->groupImages($group);

                return new CategoryListDTO(
                    name: $variant->getDisplayName(),
                    slug: (string) $variant->getSlug(),
                    price: (int) $variant->getClothes()?->getPrice(),
                    image: $images[0] ?? null,
                    images: $images,
                    colors: $this->colors($group),
                );
            },
            $groups,
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
            if ($slug !== null && $slug !== '') {
                $groups[$slug][] = $variant;
            }
        }

        return array_values($groups);
    }

    /**
     * @param non-empty-list<ClothesVariant> $variants
     *
     * @return list<string>
     */
    private function groupImages(array $variants): array
    {
        $paths = [];

        foreach ($variants as $variant) {
            foreach ($variant->getImages() ?? [] as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[$path] = $path;
                }
            }
        }

        return $this->absoluteUrls(array_values($paths));
    }

    /**
     * @param array<array-key, mixed> $paths
     *
     * @return list<string>
     */
    private function absoluteUrls(array $paths): array
    {
        $urls = [];

        foreach ($paths as $path) {
            if (!is_string($path) || $path === '') {
                continue;
            }

            $urls[] = $this->absoluteUrl($path);
        }

        return $urls;
    }

    /**
     * @param non-empty-list<ClothesVariant> $variants
     *
     * @return list<string>
     */
    private function colors(array $variants): array
    {
        $colors = [];

        foreach ($variants as $variant) {
            $color = $variant->getColor()?->getName();
            if (is_string($color) && $color !== '') {
                $colors[$color] = $color;
            }
        }

        return array_values($colors);
    }

    private function absoluteUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path) === 1 || str_starts_with($path, '//')) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return $path;
        }

        return $request->getSchemeAndHttpHost().'/'.ltrim($path, '/');
    }
}
