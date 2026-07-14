<?php

namespace App\State\Page;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Page\Service\PageContentCache;
use App\Entity\Category\Category;
use App\Entity\Clothes\ClothesVariant;
use App\Repository\Category\CategoryRepository;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<array<string, mixed>>
 */
final readonly class PageProvider implements ProviderInterface
{
    public function __construct(
        private ParameterBagInterface $parameterBag,
        private PageContentCache $pageContentCache,
        private ClothesVariantRepository $variantRepository,
        private CategoryRepository $categoryRepository,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $page = $uriVariables['page'] ?? null;

        if (!is_string($page) || !preg_match('/^[a-zA-Z0-9_-]+$/', $page)) {
            throw new NotFoundHttpException('Page introuvable.');
        }

        $path = sprintf('%s/pages/api/%s.yaml', $this->parameterBag->get('kernel.project_dir'), $page);
        if (!is_file($path)) {
            throw new NotFoundHttpException(sprintf('La page "%s" est introuvable.', $page));
        }

        $data = $this->pageContentCache->load($page, $path);

        if ($page === 'homepage') {
            $data['sections'] = is_array($data['sections'] ?? null) ? $data['sections'] : [];
            $data['sections']['bestseller'] = [
                'products' => array_map(
                    fn (ClothesVariant $variant): array => $this->mapProduct($variant, true),
                    $this->variantRepository->findHomepageBestsellers(),
                ),
            ];
            $data['sections']['highlights'] = [
                'products' => array_map(
                    fn (ClothesVariant $variant): array => $this->mapProduct($variant, false),
                    $this->variantRepository->findHomepageHighlights(),
                ),
            ];
        }

        if ($page === 'categories') {
            $data['categories'] = array_map(
                static fn (Category $category): array => [
                    'name' => (string) $category->getName(),
                    'slug' => (string) $category->getSlug(),
                    'image' => (string) $category->getImage(),
                ],
                $this->categoryRepository->findOnlineForPage(),
            );
        }

        return $this->withAbsoluteImageUrls($data);
    }

    /**
     * @return array{id: int|null, slug: string|null, name: string, price: int|null, images: string|null}
     */
    private function mapProduct(ClothesVariant $variant, bool $bestseller): array
    {
        return [
            'id' => $variant->getId(),
            'slug' => $variant->getSlug(),
            'name' => $variant->getDisplayName(),
            'price' => $variant->getClothes()?->getPrice(),
            'images' => $bestseller ? $variant->getBestsellerImage() : $variant->getHighlightImage(),
        ];
    }

    /**
     * @param array<string|int, mixed> $data
     *
     * @return array<string|int, mixed>
     */
    private function withAbsoluteImageUrls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->withAbsoluteImageUrls($value);
                continue;
            }

            if (
                is_string($key)
                && in_array($key, ['image', 'images', 'icon', 'ogImage', 'background'], true)
                && is_string($value)
                && $value !== ''
            ) {
                $data[$key] = $this->absoluteUrl($value);
            }
        }

        return $data;
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
