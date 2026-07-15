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

    public function syncClotheSizes(string $slug, array $selectedSizes, array $stocks = [], bool $confirmDelete = false): void
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
        $normalizedStocks = [];

        foreach ($selectedSizes as $sizeName) {
            $stock = filter_var($stocks[$sizeName] ?? 0, FILTER_VALIDATE_INT);
            if ($stock === false || $stock < 0) {
                throw new \InvalidArgumentException(sprintf('Le stock de la taille %s doit etre un entier positif ou nul.', $sizeName));
            }

            $normalizedStocks[$sizeName] = $stock;
        }

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
                $variant = $variantsBySize[$sizeName];
                $variant
                    ->setStock($normalizedStocks[$sizeName])
                    ->setIsOnline($normalizedStocks[$sizeName] > 0 && $variant->isOnline())
                    ->setEditedAt(new \DateTimeImmutable());
                continue;
            }

            $mainClothe->addVariant($this->createVariantForSize(
                $mainClothe,
                $firstVariant,
                $sizeName,
                $normalizedStocks[$sizeName],
            ));
        }

        $mainClothe->setEditedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    private function createVariantForSize(
        Clothes $clothe,
        ClothesVariant $sourceVariant,
        string $sizeName,
        int $stock,
    ): ClothesVariant
    {
        $size = $this->findOrCreateSize($sizeName);
        $variantName = trim(sprintf(
            '%s %s %s',
            (string) $clothe->getName(),
            (string) $sourceVariant->getColor()?->getName(),
            $sizeName,
        ));
        $now = new \DateTimeImmutable();

        return (new ClothesVariant())
            ->setName($variantName)
            ->setSlug((string) $sourceVariant->getSlug())
            ->setStock($stock)
            ->setColor($sourceVariant->getColor())
            ->setSize($size)
            ->setSku($this->buildSku($sourceVariant, $sizeName))
            ->setDescription($sourceVariant->getDescription())
            ->setMetadescription($sourceVariant->getMetadescription())
            ->setImages($sourceVariant->getImages())
            ->setHighlightImage($sourceVariant->getHighlightImage())
            ->setBestsellerImage($sourceVariant->getBestsellerImage())
            ->setIsBestseller($sourceVariant->isBestseller())
            ->setIsInCarousel($sourceVariant->isInCarousel())
            ->setIsOnline(false)
            ->setCreatedAt($now)
            ->setEditedAt($now);
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

    private function buildSku(ClothesVariant $sourceVariant, string $sizeName): string
    {
        $slug = strtolower((string) (new AsciiSlugger())->slug((string) $sourceVariant->getSlug()));

        return strtoupper(sprintf('%s-%s', $slug, $sizeName));
    }
}
