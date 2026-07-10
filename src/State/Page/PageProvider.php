<?php

namespace App\State\Page;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Pages\Page as PageDTO;
use App\Application\Clothes\Services\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Service\YamlLoaderService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PageProvider implements ProviderInterface
{

    public function __construct(
        private ParameterBagInterface $parameterBag,
        private YamlLoaderService $yamlLoader,
        private ClotheService $clotheService
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {

        $slug = $uriVariables['slug'];

        $ymlPagePath = $this->parameterBag->get('kernel.project_dir') . '/pages/api/'.$slug.'.yaml';
        
        $data = $this->yamlLoader->load($ymlPagePath);
        foreach ($data["sections"] as &$section) {
            if ($section["type"] === "product_list") {
                
                $section['content']["products"] = array_map(
                    fn (Clothes $clothe): array => $this->mapProduct($clothe),
                    $this->clotheService->getBestselledClothe(4),
                );
                
                
            }
        }

        $page = new PageDTO;
        $page->slug = $data["slug"];
        $page->seo = $data["seo"];
        $page->section = $data["sections"];







        // Retrieve the state from somewhere
        return $page;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapProduct(Clothes $clothe): array
    {
        $variants = array_values(array_filter(
            $clothe->getVariants()->toArray(),
            static fn (mixed $variant): bool => $variant instanceof ClothesVariant,
        ));
        $defaultVariant = $this->findDefaultVariant($variants);

        return [
            'id' => $clothe->getId(),
            'name' => $clothe->getName(),
            'slug' => $clothe->getSlug(),
            'description' => $clothe->getDescription(),
            'metadescription' => $defaultVariant?->getMetadescription(),
            'price' => $clothe->getPrice(),
            'collection' => $clothe->getCollection()?->getName(),
            'category' => $clothe->getCollection()?->getCategory()?->getName(),
            'isOnline' => (bool) $clothe->isOnline(),
            'isBestseller' => (bool) $clothe->isBestseller(),
            'isInCarousel' => (bool) $clothe->isInCarousel(),
            'images' => [
                'highlight' => $defaultVariant?->getHighlightImage(),
                'bestseller' => $defaultVariant?->getBestsellerImage(),
                'gallery' => $defaultVariant?->getImages() ?? [],
            ],
            'variants' => array_map(
                fn (ClothesVariant $variant): array => $this->mapVariant($variant),
                $variants,
            ),
        ];
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function findDefaultVariant(array $variants): ?ClothesVariant
    {
        foreach ($variants as $variant) {
            if ($variant->isAvailable()) {
                return $variant;
            }
        }

        return $variants[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapVariant(ClothesVariant $variant): array
    {
        return [
            'id' => $variant->getId(),
            'name' => $variant->getDisplayName(),
            'sku' => $variant->getSku(),
            'description' => $variant->getDescription() ?? $variant->getClothes()?->getDescription(),
            'metadescription' => $variant->getMetadescription(),
            'color' => [
                'id' => $variant->getColor()?->getId(),
                'name' => $variant->getColor()?->getName(),
                'hexa' => $variant->getColor()?->getHexa(),
            ],
            'size' => [
                'id' => $variant->getSize()?->getId(),
                'name' => $variant->getSize()?->getName(),
            ],
            'stock' => $variant->getStock(),
            'isOnline' => $variant->isOnline(),
            'isAvailable' => $variant->isAvailable(),
            'images' => [
                'highlight' => $variant->getHighlightImage(),
                'bestseller' => $variant->getBestsellerImage(),
                'gallery' => $variant->getImages() ?? [],
            ],
        ];
    }
}
