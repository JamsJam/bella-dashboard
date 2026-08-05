<?php

namespace App\Repository\Clothes;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use Doctrine\Persistence\ManagerRegistry;
use App\Enum\ClotheStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Clothes>
 */
class ClothesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Clothes::class);
    }

    public function findOneByVariantSlug(string $slug): ?Clothes
    {
        $clothe = $this->createQueryBuilder('clothe')
            ->innerJoin('clothe.variants', 'variant')
            ->andWhere('variant.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $clothe instanceof Clothes ? $clothe : null;
    }

    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findDistinctEntitiesByName(
        ?string $orderBy = 'c.name',
        ?string $direction = 'asc',
        ?string $query = null,
        ?int $category = null,
        ?int $collection = null,
        bool $bestsellerOnly = false,
        ?bool $online = null,
        ?int $limit = null,
        ?int $offset = null,
    ): array {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->addSelect('col', 'cat', 'variants', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->leftJoin('variants.color', 'cc')
            ->leftJoin('variants.size', 'cs')
            ->orderBy($orderBy ?? 'c.name', $direction ?? 'asc')
            ->addOrderBy('variants.publicationStatus', 'asc')
            ->addOrderBy('c.id', 'asc')
        ;

        if ($query !== null && trim($query) !== '') {
            $qb
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->like('c.name', ':query'),
                        $qb->expr()->like('col.name', ':query'),
                        $qb->expr()->like('cat.name', ':query'),
                        $qb->expr()->like('cc.name', ':query'),
                    ),
                )
                ->setParameter('query', '%'.trim($query).'%')
            ;
        }

        if ($category !== null && $category > 0) {
            $qb
                ->andWhere('cat.id = :category')
                ->setParameter('category', $category)
            ;
        }

        if ($collection !== null && $collection > 0) {
            $qb
                ->andWhere('col.id = :collection')
                ->setParameter('collection', $collection)
            ;
        }

        if ($bestsellerOnly) {
            $qb->andWhere('variants.isBestseller = true');
        }

        if ($online === true) {
            $qb
                ->andWhere('cat.isOnline = true')
                ->andWhere('col.isOnline = true')
                ->andWhere('variants.publicationStatus = :onlineStatus')
                ->setParameter('onlineStatus', ClotheStatus::Online)
            ;
        } elseif ($online === false) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'cat.isOnline = false',
                    'col.isOnline = false',
                    'variants.publicationStatus <> :onlineStatus',
                    'variants.id IS NULL',
                ),
            )->setParameter('onlineStatus', ClotheStatus::Online);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return ClothesVariant[]
     */
    public function findVariantsBySlug(string $slug): array
    {
        $clothe = $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'variants', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->join('c.variants', 'slugVariant')
            ->leftJoin('c.variants', 'variants')
            ->leftJoin('variants.color', 'cc')
            ->leftJoin('variants.size', 'cs')
            ->andWhere('slugVariant.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('cs.name', 'ASC')
            ->addOrderBy('variants.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();

        if (!$clothe instanceof Clothes) {
            return [];
        }

        return array_values(array_filter(
            $clothe->getVariants()->toArray(),
            static fn (ClothesVariant $variant): bool => $variant->getSlug() === $slug,
        ));
    }

    public function findOneOnlineBySlugWithVariants(string $slug): ?Clothes
    {
        $clothe = $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'variants', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->join('c.variants', 'slugVariant')
            ->join('c.variants', 'variants')
            ->join('variants.color', 'cc')
            ->join('variants.size', 'cs')
            ->andWhere('slugVariant.slug = :slug')
            ->andWhere('variants.publicationStatus = :onlineStatus')
            ->andWhere('col.isOnline = true')
            ->andWhere('cat.isOnline = true')
            ->setParameter('onlineStatus', ClotheStatus::Online)
            ->setParameter('slug', $slug)
            ->orderBy('cc.name', 'ASC')
            ->addOrderBy('cs.name', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();

        return $clothe instanceof Clothes ? $clothe : null;
    }

    public function findOneByNameOrSlugExcludingSlug(string $name, string $slug, ?string $excludedSlug = null): ?Clothes
    {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->leftJoin('c.variants', 'variants')
            ->andWhere($qb->expr()->orX('LOWER(c.name) = :name', 'variants.slug = :slug'))
            ->setParameter('name', mb_strtolower($name))
            ->setParameter('slug', $slug)
            ->setMaxResults(1);

        if ($excludedSlug !== null && $excludedSlug !== '') {
            $qb
                ->andWhere(sprintf(
                    'c.id NOT IN (SELECT excludedClothe.id FROM %s excludedClothe JOIN excludedClothe.variants excludedVariant WHERE excludedVariant.slug = :excludedSlug)',
                    Clothes::class,
                ))
                ->setParameter('excludedSlug', $excludedSlug);
        }

        $clothe = $qb->getQuery()->getOneOrNullResult();

        return $clothe instanceof Clothes ? $clothe : null;
    }

    /**
     * @return Clothes[]
     */
    public function findDistinctCollectionItemsBySlug(string $slug, int $limit = 8): array
    {
        $reference = $this->createQueryBuilder('c')
            ->join('c.variants', 'referenceVariant')
            ->andWhere('referenceVariant.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$reference instanceof Clothes || $reference->getCollection() === null) {
            return [];
        }

        $results = $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'variants', 'cc')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->leftJoin('variants.color', 'cc')
            ->andWhere('col = :collection')
            ->andWhere('c.id != :referenceId')
            ->setParameter('collection', $reference->getCollection())
            ->setParameter('referenceId', $reference->getId())
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        return array_slice($results, 0, $limit);
    }

    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findClothesInCollection($collection): array
    {
        $qb= $this->createQueryBuilder('c');
        $qb->select('DISTINCT c.name, variants.images, variants.publicationStatus, co.name As collectionName, cc.name AS colorName, c.createdAt')
            ->leftJoin('c.collection','co')
            ->leftJoin('c.variants','variants')
            ->leftJoin('variants.size','cs')
            ->leftJoin('variants.color','cc')
            ->andWhere($qb->expr()->eq('co.id', ':collection'))
            ->setParameter(':collection', $collection->getId())

            ->orderBy('c.createdAt', 'ASC')
            
            ;
            dd($qb->getQuery()->getResult());
        return $qb->getQuery()->getResult();
    }
    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findDistinctBySlug(?string $orderBy, ?string $direction, ?string $query = null, ?int $limit = null, ?int $offset = null): array
    {

        $limit = $limit ?? 10;
        $offset = $offset ?? 0;

        $qb = $this->createQueryBuilder('c');

        $qb ->select('variants.slug, c.name, col.name AS collection , cat.name AS category, variants.publicationStatus')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->groupBy('variants.slug, c.name, col.name, cat.name, variants.publicationStatus')
            ->orderBy($orderBy,$direction)
            ->setMaxResults( $limit )
        ;

        if (isset($query) && strlen($query) > 0){
            $qb->where(
                $qb->expr()->orX(
                    $qb->expr()->like('c.name', ':query'),
                    $qb->expr()->like('col.name', ':query'),
                    $qb->expr()->like('cat.name', ':query'),
                    $qb->expr()->like('cat.isOnline', ':query')
                )
            )
            ->setParameter('query','%' . $query . '%')
            ;
        }

        return $qb->getQuery()->getResult();
    }
    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findBestSellersDistinctBySlug(?int $limit ): array
    {

        $qb = $this->createQueryBuilder('c');

        $qb ->select('variants.slug, c.name, col.name AS collection , cat.name AS category')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->groupBy('variants.slug, c.name, col.name, cat.name')
            ->orderBy('c.name',"asc")
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq(
                    'variants.isBestseller', true),
            ))
            ->setMaxResults( $limit )
        ;


        return $qb->getQuery()->getResult();
    }

    /**
     * @return Clothes[]
     */
    public function findDistinctBestsellerEntities(?int $limit = null): array
    {
        $results = $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'variants', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->leftJoin('variants.color', 'cc')
            ->leftJoin('variants.size', 'cs')
            ->andWhere('variants.isBestseller = true')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $limit !== null ? array_slice($results, 0, $limit) : $results;
    }

    /**
     * @param list<int> $ids
     * @return Clothes[]
     */
    public function findDistinctEntitiesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $results = $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'variants', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->leftJoin('variants.color', 'cc')
            ->leftJoin('variants.size', 'cs')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * @param list<string> $slugs
     * @return Clothes[]
     */
    public function findDistinctEntitiesBySlugs(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter($slugs)));

        if ($slugs === []) {
            return [];
        }

        $results = $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'variants', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->leftJoin('variants.color', 'cc')
            ->leftJoin('variants.size', 'cs')
            ->join('c.variants', 'slugVariant')
            ->andWhere('slugVariant.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }


    /**
     * @param list<int> $ids
     * @return Clothes[]
     */
    public function findByIdsPreservingOrder(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));

        if ($ids === []) {
            return [];
        }

        $clothesById = [];
        foreach ($this->findBy(['id' => $ids]) as $clothe) {
            if ($clothe instanceof Clothes && $clothe->getId() !== null) {
                $clothesById[$clothe->getId()] = $clothe;
            }
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($clothesById[$id])) {
                $ordered[] = $clothesById[$id];
            }
        }

        return $ordered;
    }

    /**
     * @param list<string> $slugs
     * @return ClothesVariant[]
     */
    public function findVariantsBySlugs(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter($slugs)));

        if ($slugs === []) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('variants')
            ->from(ClothesVariant::class, 'variants')
            ->andWhere('variants.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->orderBy('variants.slug', 'ASC')
            ->addOrderBy('variants.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ClothesVariant[]
     */
    public function findBestsellerVariants(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('variants')
            ->from(ClothesVariant::class, 'variants')
            ->andWhere('variants.isBestseller = true')
            ->orderBy('variants.slug', 'ASC')
            ->addOrderBy('variants.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Clothes[]
     */
    public function findDistinctFeaturedEntities(): array
    {
        $results = $this->createQueryBuilder('c')
            ->addSelect('col', 'variants')
            ->join('c.collection', 'col')
            ->join('c.variants', 'variants')
            ->andWhere('variants.isInCarousel = true')
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        $clothesBySlug = [];
        foreach ($results as $clothe) {
            if ($clothe instanceof Clothes && $clothe->getSlug() !== null) {
                $clothesBySlug[$clothe->getSlug()] ??= $clothe;
            }
        }

        return array_values($clothesBySlug);
    }

    /**
     * @return ClothesVariant[]
     */
    public function findFeaturedVariants(): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('variants')
            ->from(ClothesVariant::class, 'variants')
            ->andWhere('variants.isInCarousel = true')
            ->orderBy('variants.slug', 'ASC')
            ->addOrderBy('variants.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findInCarouselDistinctBySlug(?int $limit): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb ->select('variants.slug, c.name, col.name AS collection , cat.name AS category')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.variants', 'variants')
            ->groupBy('variants.slug, c.name, col.name, cat.name')
            ->orderBy('c.name',"asc")
            ->andWhere('variants.isInCarousel = true')
        ;

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }


}
