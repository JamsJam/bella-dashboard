<?php

namespace App\Repository\Clothes;

use App\Enum\ClotheStatus;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClothesVariant>
 */
class ClothesVariantRepository extends ServiceEntityRepository
{
    /** @return list<ClothesVariant> */
    public function findScheduledForPublication(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('variant')
            ->andWhere('variant.publicationStatus = :status')
            ->andWhere('variant.scheduledPublicationAt <= :now')
            ->setParameter('status', ClotheStatus::Scheduled)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClothesVariant::class);
    }

    public function countLowStock(int $threshold = 5): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.stock <= :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findOneWithProduct(int $id): ?ClothesVariant
    {
        $variant = $this->createQueryBuilder('v')
            ->addSelect('c', 'color', 'size', 'collection', 'category')
            ->join('v.clothes', 'c')
            ->join('v.color', 'color')
            ->join('v.size', 'size')
            ->join('c.collection', 'collection')
            ->join('collection.category', 'category')
            ->andWhere('v.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $variant instanceof ClothesVariant ? $variant : null;
    }

    public function findOneWithProductForUpdate(int $id): ?ClothesVariant
    {
        $variant = $this->createQueryBuilder('v')
            ->addSelect('c', 'color', 'size')
            ->join('v.clothes', 'c')
            ->join('v.color', 'color')
            ->join('v.size', 'size')
            ->andWhere('v.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $variant instanceof ClothesVariant ? $variant : null;
    }

    public function findOneByProductColorAndSize(Clothes $clothes, Clothescolor $color, Clothessize $size): ?ClothesVariant
    {
        return $this->findOneBy([
            'clothes' => $clothes,
            'color' => $color,
            'size' => $size,
        ]);
    }

    /**
     * @return list<ClothesVariant>
     */
    public function findOnlineByCategory(int $categoryId): array
    {
        return $this->createQueryBuilder('variant')
            ->addSelect('clothes', 'color', 'size', 'collection', 'category', 'availableVariant', 'availableColor')
            ->join('variant.clothes', 'clothes')
            ->join('variant.color', 'color')
            ->join('variant.size', 'size')
            ->leftJoin('clothes.variants', 'availableVariant')
            ->leftJoin('availableVariant.color', 'availableColor')
            ->join('clothes.collection', 'collection')
            ->join('collection.category', 'category')
            ->andWhere('category.id = :category')
            ->andWhere('category.isOnline = true')
            ->andWhere('collection.isOnline = true')
            ->andWhere('variant.publicationStatus = :onlineStatus')
            ->setParameter('category', $categoryId)
            ->setParameter('onlineStatus', ClotheStatus::Online)
            ->orderBy('clothes.name', 'ASC')
            ->addOrderBy('color.name', 'ASC')
            ->addOrderBy('size.name', 'ASC')
            ->addOrderBy('variant.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ClothesVariant>
     */
    public function findOnlineBySlug(string $slug): array
    {
        return $this->createQueryBuilder('variant')
            ->addSelect('clothes', 'color', 'size', 'collection', 'category', 'sizeGuide', 'guideSize', 'measurement', 'measurementType')
            ->join('variant.clothes', 'clothes')
            ->join('variant.color', 'color')
            ->join('variant.size', 'size')
            ->join('clothes.collection', 'collection')
            ->join('collection.category', 'category')
            ->leftJoin('variant.sizeGuide', 'sizeGuide')
            ->leftJoin('sizeGuide.sizes', 'guideSize')
            ->leftJoin('guideSize.measurements', 'measurement')
            ->leftJoin('measurement.type', 'measurementType')
            ->andWhere('variant.slug = :slug')
            ->andWhere('category.isOnline = true')
            ->andWhere('collection.isOnline = true')
            ->andWhere('variant.publicationStatus = :onlineStatus')
            ->setParameter('slug', $slug)
            ->setParameter('onlineStatus', ClotheStatus::Online)
            ->orderBy('size.name', 'ASC')
            ->addOrderBy('variant.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ClothesVariant>
     */
    public function findOnlineByCollection(int $collectionId): array
    {
        return $this->createQueryBuilder('variant')
            ->addSelect('clothes', 'color', 'size', 'collection', 'category')
            ->join('variant.clothes', 'clothes')
            ->join('variant.color', 'color')
            ->join('variant.size', 'size')
            ->join('clothes.collection', 'collection')
            ->join('collection.category', 'category')
            ->andWhere('collection.id = :collection')
            ->andWhere('category.isOnline = true')
            ->andWhere('collection.isOnline = true')
            ->andWhere('variant.publicationStatus = :onlineStatus')
            ->setParameter('collection', $collectionId)
            ->setParameter('onlineStatus', ClotheStatus::Online)
            ->orderBy('clothes.name', 'ASC')
            ->addOrderBy('color.name', 'ASC')
            ->addOrderBy('size.name', 'ASC')
            ->addOrderBy('variant.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<string> $colors
     * @param list<string> $sizes
     *
     * @return list<ClothesVariant>
     */
    public function searchOnlineByCategory(
        int $categoryId,
        array $colors = [],
        array $sizes = [],
        ?int $minimumPrice = null,
        ?int $maximumPrice = null,
    ): array {
        $qb = $this->createQueryBuilder('variant')
            ->addSelect('clothes', 'color', 'size', 'collection', 'category', 'availableVariant', 'availableColor', 'availableSize')
            ->join('variant.clothes', 'clothes')
            ->join('variant.color', 'color')
            ->join('variant.size', 'size')
            ->leftJoin('clothes.variants', 'availableVariant')
            ->leftJoin('availableVariant.color', 'availableColor')
            ->leftJoin('availableVariant.size', 'availableSize')
            ->join('clothes.collection', 'collection')
            ->join('collection.category', 'category')
            ->andWhere('category.id = :category')
            ->andWhere('category.isOnline = true')
            ->andWhere('collection.isOnline = true')
            ->andWhere('variant.publicationStatus = :onlineStatus')
            ->setParameter('category', $categoryId)
            ->setParameter('onlineStatus', ClotheStatus::Online)
            ->orderBy('clothes.name', 'ASC')
            ->addOrderBy('color.name', 'ASC')
            ->addOrderBy('size.name', 'ASC')
            ->addOrderBy('variant.id', 'ASC');

        if ($colors !== []) {
            $qb
                ->andWhere('LOWER(color.name) IN (:colors)')
                ->setParameter('colors', array_map(static fn (string $color): string => mb_strtolower($color), $colors));
        }

        if ($sizes !== []) {
            $qb
                ->andWhere('LOWER(size.name) IN (:sizes)')
                ->setParameter('sizes', array_map(static fn (string $size): string => mb_strtolower($size), $sizes));
        }

        if ($minimumPrice !== null) {
            $qb
                ->andWhere('clothes.price >= :minimumPrice')
                ->setParameter('minimumPrice', $minimumPrice);
        }

        if ($maximumPrice !== null) {
            $qb
                ->andWhere('clothes.price <= :maximumPrice')
                ->setParameter('maximumPrice', $maximumPrice);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<ClothesVariant>
     */
    public function findHomepageBestsellers(): array
    {
        return $this->findGroupsBySlug(bestsellerOnly: true);
    }

    /**
     * `isInCarousel` est l'indicateur actuellement persisté pour les highlights.
     *
     * @return list<ClothesVariant>
     */
    public function findHomepageHighlights(): array
    {
        return $this->findHomepageVariantsByFlag('isInCarousel');
    }

    /**
     * @return list<ClothesVariant>
     */
    private function findHomepageVariantsByFlag(string $flag): array
    {
        return $this->createQueryBuilder('v')
            ->addSelect('clothes', 'color', 'size')
            ->join('v.clothes', 'clothes')
            ->join('v.color', 'color')
            ->join('v.size', 'size')
            ->andWhere(sprintf('v.%s = true', $flag))
            ->orderBy('v.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<ClothesVariant>
     */
    public function findGroupsBySlug(
        ?string $orderBy = 'c.name',
        ?string $direction = 'asc',
        ?string $query = null,
        ?int $category = null,
        ?int $collection = null,
        bool $bestsellerOnly = false,
        ?ClotheStatus $status = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $qb = $this->createQueryBuilder('v')
            ->addSelect('c', 'color', 'size', 'collection', 'category')
            ->join('v.clothes', 'c')
            ->join('v.color', 'color')
            ->join('v.size', 'size')
            ->join('c.collection', 'collection')
            ->join('collection.category', 'category')
            ->orderBy($orderBy ?? 'c.name', $direction ?? 'asc')
            ->addOrderBy('color.name', 'ASC')
            ->addOrderBy('size.name', 'ASC')
            ->addOrderBy('v.id', 'ASC');

        if ($query !== null && trim($query) !== '') {
            $qb
                ->andWhere($qb->expr()->orX(
                    'LOWER(c.name) LIKE :query',
                    'LOWER(v.name) LIKE :query',
                    'LOWER(v.slug) LIKE :query',
                    'LOWER(collection.name) LIKE :query',
                    'LOWER(category.name) LIKE :query',
                    'LOWER(color.name) LIKE :query',
                ))
                ->setParameter('query', '%'.mb_strtolower(trim($query)).'%');
        }

        if ($category !== null && $category > 0) {
            $qb
                ->andWhere('category.id = :category')
                ->setParameter('category', $category);
        }

        if ($collection !== null && $collection > 0) {
            $qb
                ->andWhere('collection.id = :collection')
                ->setParameter('collection', $collection);
        }

        if ($bestsellerOnly) {
            $qb->andWhere('v.isBestseller = true');
        }

        if ($status instanceof ClotheStatus) {
            $qb->andWhere('v.publicationStatus = :status')->setParameter('status', $status);
        }

        $groups = [];
        foreach ($qb->getQuery()->getResult() as $variant) {
            if (!$variant instanceof ClothesVariant || $variant->getSlug() === null) {
                continue;
            }

            $groups[$variant->getSlug()] ??= $variant;
        }

        $groups = array_values($groups);

        if ($offset !== null && $offset > 0) {
            $groups = array_slice($groups, $offset);
        }

        if ($limit !== null) {
            $groups = array_slice($groups, 0, $limit);
        }

        return $groups;
    }
}
