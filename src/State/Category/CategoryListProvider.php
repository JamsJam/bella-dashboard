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

        $category = $this->categoryRepository->findOneBy([
            'slug' => $categorySlug,
            'isOnline' => true,
        ]);

        if (!$category instanceof Category || $category->getId() === null) {
            throw new NotFoundHttpException(sprintf('La catégorie "%s" est introuvable.', $categorySlug));
        }

        return array_map(
            function (ClothesVariant $variant): CategoryListDTO {
                $images = $this->absoluteUrls($variant->getImages() ?? []);

                return new CategoryListDTO(
                    name: $variant->getDisplayName(),
                    slug: (string) $variant->getSlug(),
                    image: $images[0] ?? null,
                    images: $images,
                    colors: $this->colors($variant),
                );
            },
            $this->variantRepository->findOnlineByCategory($category->getId()),
        );
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
     * @return list<string>
     */
    private function colors(ClothesVariant $variant): array
    {
        $colors = [];

        foreach ($variant->getClothes()?->getVariants() ?? [] as $clothesVariant) {
            $color = $clothesVariant->getColor()?->getName();
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
