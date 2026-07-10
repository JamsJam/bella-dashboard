<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Provider\ClotheProvider\ClotheProvider;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothessize;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ClotheService 
{
    public const AVAILABLE_SIZES = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'];

    public function __construct(
        private ClotheProvider $clotheProvider,
        private EntityManagerInterface $entityManager,
    ){}

    public function getDistinctClothe(?string $sortBy = 'id', ?string $direction = 'asc', ?string $query = '',?int $limit = null, ?int $offset = null) : array
    {

        $clothes = $this->clotheProvider->searchDistinctClothes($sortBy, $direction, $query, $limit, $offset) ?? [];
        // $clothes = [];
        
        return $clothes;
    }

    public function getDistinctClotheByName(
        ?string $sortBy = 'name',
        ?string $direction = 'asc',
        ?string $query = '',
        ?int $category = null,
        ?int $collection = null,
        bool $bestsellerOnly = false,
        ?bool $online = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        return $this->clotheProvider->searchDistinctClothesByName(
            $sortBy,
            $direction,
            $query,
            $category,
            $collection,
            $bestsellerOnly,
            $online,
            $limit,
            $offset,
        ) ?? [];
    }

    public function getBestselledClothe(?int $limit = null) : array
    {
        return $this->clotheProvider->getBestSellerEntities($limit) ?? [];
    }

    public function getClotheInCarousel(?int $limit = null) : array
    {

        $clothes = $this->clotheProvider->getClotheInCarousel($limit) ?? [];
        // $clothes = [];
        return $clothes;
    }

    public function getTotalItems() : array
    {

        // $totalItems = $this->clotheProvider->gettotalItems() ?? [];
        $clothes = [];
        // return $totalItems;
        return $clothes;
    }

    public function getClotheVariantsBySlug(string $slug): array
    {
        return $this->clotheProvider->getClotheVariantsBySlug($slug);
    }

    public function getSameCollectionClothes(string $slug, int $limit = 8): array
    {
        return $this->clotheProvider->getSameCollectionClothes($slug, $limit);
    }

    public function syncClotheSizes(string $slug, array $selectedSizes, bool $confirmDelete = false): void
    {
        $selectedSizes = array_values(array_intersect(self::AVAILABLE_SIZES, $selectedSizes));
        $variants = $this->getClotheVariantsBySlug($slug);

        if ($variants === []) {
            throw new \InvalidArgumentException('Clothe not found.');
        }

        $firstVariant = $variants[0] ?? null;
        if (!$firstVariant instanceof ClothesVariant || !$firstVariant->getClothes() instanceof Clothes) {
            throw new \InvalidArgumentException('Clothe not found.');
        }

        $mainClothe = $firstVariant->getClothes();
        $variantsBySize = [];

        foreach ($variants as $variant) {
            if ($variant instanceof ClothesVariant && $variant->getSize()?->getName() !== null) {
                $variantsBySize[$variant->getSize()->getName()] = $variant;
            }
        }

        $sizesToDelete = array_diff(array_keys($variantsBySize), $selectedSizes);
        if ($sizesToDelete !== [] && !$confirmDelete) {
            throw new \RuntimeException('delete_confirmation_required');
        }

        foreach ($sizesToDelete as $sizeName) {
            $this->entityManager->remove($variantsBySize[$sizeName]);
        }

        foreach ($selectedSizes as $sizeName) {
            if (isset($variantsBySize[$sizeName])) {
                continue;
            }

            $mainClothe->addVariant($this->createVariantForSize($mainClothe, $sizeName));
        }

        $this->entityManager->flush();
    }

    private function createVariantForSize(Clothes $source, string $sizeName): ClothesVariant
    {
        $size = $this->findOrCreateSize($sizeName);
        $variantName = trim(sprintf(
            '%s %s %s',
            (string) $source->getName(),
            (string) $source->getColor()?->getName(),
            $sizeName,
        ));

        return (new ClothesVariant())
            ->setName($variantName)
            ->setSlug(strtolower((string) (new AsciiSlugger())->slug($variantName)))
            ->setStock(0)
            ->setColor($source->getColor())
            ->setSize($size)
            ->setSku($this->buildSku($source, $sizeName))
            ->setImages($source->getImages())
            ->setHighlightImage(($source->getImages() ?? [])[0] ?? null)
            ->setBestsellerImage(($source->getImages() ?? [])[0] ?? null)
            ->setIsOnline(false);
    }

    private function findOrCreateSize(string $sizeName): Clothessize
    {
        $sizeRepository = $this->entityManager->getRepository(Clothessize::class);
        $size = $sizeRepository->findOneBy(['name' => $sizeName]);

        if ($size instanceof Clothessize) {
            return $size;
        }

        $size = (new Clothessize())->setName($sizeName);
        if (method_exists($size, 'setCreatedAt')) {
            $size->setCreatedAt(new \DateTimeImmutable());
        }

        if (method_exists($size, 'setEditedAt')) {
            $size->setEditedAt(new \DateTimeImmutable());
        }

        $this->entityManager->persist($size);

        return $size;
    }

    private function buildSku(Clothes $source, string $sizeName): string
    {
        $slug = strtolower((string) (new AsciiSlugger())->slug((string) $source->getSlug()));

        return strtoupper(sprintf('%s-%s-%s', $slug, $sizeName, bin2hex(random_bytes(2))));
    }
}
