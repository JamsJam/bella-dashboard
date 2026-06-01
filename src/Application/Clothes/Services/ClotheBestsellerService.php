<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Model\BestsellerUpdateResult;
use App\Application\Clothes\Provider\ClotheProvider\ClotheProvider;
use App\Entity\Clothes\Clothes;
use App\Repository\Clothes\ClothesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class ClotheBestsellerService
{
    public const CACHE_KEY = 'BESTSELLER';

    public function __construct(
        private ClotheProvider $clotheProvider,
        private ClothesRepository $clothesRepository,
        private EntityManagerInterface $entityManager,
        private CacheInterface $cache,
        #[Autowire('%app.clothes.bestseller.max_items%')]
        private int $maxItems,
    ) {
    }

    /**
     * @return list<Clothes>
     */
    public function getBestsellers(): array
    {
        $ids = $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(3600);

            return array_values(array_filter(array_map(
                static fn (Clothes $clothe): ?int => $clothe->getId(),
                $this->clotheProvider->getBestSellerEntities(),
            )));
        });

        return $this->clothesRepository->findByIdsPreservingOrder($ids);
    }

    /**
     * @return list<Clothes>
     */
    public function createCacheIfMissing(): array
    {
        return $this->getBestsellers();
    }

    public function invalidateCache(): void
    {
        $this->cache->delete(self::CACHE_KEY);
    }

    /**
     * @param list<int> $ids
     */
    public function addByIds(array $ids, bool $pruneOverflow = false): BestsellerUpdateResult
    {
        $currentSlugs = $this->extractSlugs($this->getBestsellers());
        $addedSlugs = $this->extractSlugs($this->clothesRepository->findDistinctEntitiesByIds($ids));

        return $this->saveSlugs(array_values(array_unique([...$currentSlugs, ...$addedSlugs])), $pruneOverflow);
    }

    /**
     * @param list<int> $ids
     */
    public function replaceByIds(array $ids, bool $pruneOverflow = false): BestsellerUpdateResult
    {
        return $this->saveSlugs(
            $this->extractSlugs($this->clothesRepository->findDistinctEntitiesByIds($ids)),
            $pruneOverflow,
        );
    }

    /**
     * @param list<string> $slugs
     */
    public function removeBySlugs(array $slugs): BestsellerUpdateResult
    {
        $removedSlugMap = array_flip(array_values(array_unique(array_filter($slugs))));

        foreach ($this->clothesRepository->findBestsellerVariants() as $variant) {
            if ($variant instanceof Clothes && isset($removedSlugMap[(string) $variant->getSlug()])) {
                $variant->setIsBestseller(false);
            }
        }

        $this->entityManager->flush();
        $this->invalidateCache();

        return new BestsellerUpdateResult(
            success: true,
            requiresPruning: false,
            bestsellers: $this->getBestsellers(),
            overflow: [],
            maxItems: $this->maxItems,
            message: 'Liste bestseller mise a jour.',
        );
    }


    public function getMaxItems(): int
    {
        return $this->maxItems;
    }

    /**
     * @param list<string> $slugs
     */
    private function saveSlugs(array $slugs, bool $pruneOverflow): BestsellerUpdateResult
    {
        $slugs = array_values(array_unique(array_filter($slugs)));
        $overflowSlugs = array_slice($slugs, $this->maxItems);

        if ($overflowSlugs !== [] && !$pruneOverflow) {
            return new BestsellerUpdateResult(
                success: false,
                requiresPruning: true,
                bestsellers: $this->clothesRepository->findDistinctEntitiesBySlugs(array_slice($slugs, 0, $this->maxItems)),
                overflow: $this->clothesRepository->findDistinctEntitiesBySlugs($overflowSlugs),
                maxItems: $this->maxItems,
                message: sprintf('La liste bestseller ne peut pas contenir plus de %d elements.', $this->maxItems),
            );
        }

        $keptSlugs = array_slice($slugs, 0, $this->maxItems);
        $keptSlugMap = array_flip($keptSlugs);

        foreach ($this->clothesRepository->findBestsellerVariants() as $variant) {
            if ($variant instanceof Clothes && !isset($keptSlugMap[(string) $variant->getSlug()])) {
                $variant->setIsBestseller(false);
            }
        }

        foreach ($this->clothesRepository->findVariantsBySlugs($keptSlugs) as $variant) {
            if ($variant instanceof Clothes) {
                $variant->setIsBestseller(true);
            }
        }

        $this->entityManager->flush();
        $this->invalidateCache();

        return new BestsellerUpdateResult(
            success: true,
            requiresPruning: false,
            bestsellers: $this->getBestsellers(),
            overflow: [],
            maxItems: $this->maxItems,
            message: 'Liste bestseller mise a jour.',
        );
    }

    /**
     * @param list<Clothes> $clothes
     * @return list<string>
     */
    private function extractSlugs(array $clothes): array
    {
        return array_values(array_filter(array_map(
            static fn (Clothes $clothe): ?string => $clothe->getSlug(),
            $clothes,
        )));
    }
}
