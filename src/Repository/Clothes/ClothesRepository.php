<?php

namespace App\Repository\Clothes;

use App\Entity\Clothes\Clothes;
use Doctrine\ORM\Query\Parameter;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
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
            ->addSelect('col', 'cat', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.color', 'cc')
            ->leftJoin('c.size', 'cs')
            ->orderBy($orderBy ?? 'c.name', $direction ?? 'asc')
            ->addOrderBy('c.isOnline', 'desc')
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
            $qb->andWhere('c.isBestseller = true');
        }

        if ($online === true) {
            $qb
                ->andWhere('cat.isOnline = true')
                ->andWhere('col.isOnline = true')
                ->andWhere(
                    $qb->expr()->exists(
                        $this->createQueryBuilder('onlineVariant')
                            ->select('1')
                            ->andWhere('onlineVariant.slug = c.slug')
                            ->andWhere('onlineVariant.isOnline = true')
                            ->getDQL(),
                    ),
                )
            ;
        } elseif ($online === false) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'cat.isOnline = false',
                    'col.isOnline = false',
                    $qb->expr()->not(
                        $qb->expr()->exists(
                            $this->createQueryBuilder('onlineVariant')
                                ->select('1')
                                ->andWhere('onlineVariant.slug = c.slug')
                                ->andWhere('onlineVariant.isOnline = true')
                                ->getDQL(),
                        ),
                    ),
                ),
            );
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }

        $clothesBySlug = [];

        foreach ($qb->getQuery()->getResult() as $clothe) {
            if (!$clothe instanceof Clothes || $clothe->getSlug() === null) {
                continue;
            }

            $clothesBySlug[$clothe->getSlug()] ??= $clothe;
        }

        return array_values($clothesBySlug);
    }

    /**
     * @return Clothes[]
     */
    public function findVariantsBySlug(string $slug): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.color', 'cc')
            ->leftJoin('c.size', 'cs')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('cs.name', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Clothes[]
     */
    public function findDistinctCollectionItemsBySlug(string $slug, int $limit = 8): array
    {
        $reference = $this->findOneBy(['slug' => $slug]);
        if (!$reference instanceof Clothes || $reference->getCollection() === null) {
            return [];
        }

        $results = $this->createQueryBuilder('c')
            ->addSelect('col', 'cat', 'cc')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.color', 'cc')
            ->andWhere('col = :collection')
            ->andWhere('c.slug != :slug')
            ->setParameter('collection', $reference->getCollection())
            ->setParameter('slug', $slug)
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

        return array_slice(array_values($clothesBySlug), 0, $limit);
    }

    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findClothesInCollection($collection): array
    {
        $qb= $this->createQueryBuilder('c');
        $qb->select('DISTINCT c.name, c.images, c.isOnline, co.name As collectionName, cc.name AS colorName, c.createdAt')
            ->leftJoin('c.collection','co')
            ->leftJoin('c.size','cs')
            ->leftJoin('c.color','cc')
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

        $qb ->select('c.slug, c.name, col.name AS collection , cat.name AS category, c.isOnline')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->groupBy('c.slug, c.name, col.name, cat.name, c.isOnline')
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

        $qb ->select('c.slug, c.name, col.name AS collection , cat.name AS category')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->groupBy('c.slug, c.name, col.name, cat.name')
            ->orderBy('c.name',"asc")
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq(
                    'c.isBestseller', true),
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
            ->addSelect('col', 'cat', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.color', 'cc')
            ->leftJoin('c.size', 'cs')
            ->andWhere('c.isBestseller = true')
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

        $clothes = array_values($clothesBySlug);

        return $limit !== null ? array_slice($clothes, 0, $limit) : $clothes;
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
            ->addSelect('col', 'cat', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.color', 'cc')
            ->leftJoin('c.size', 'cs')
            ->andWhere('c.id IN (:ids)')
            ->setParameter('ids', $ids)
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
            ->addSelect('col', 'cat', 'cc', 'cs')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->leftJoin('c.color', 'cc')
            ->leftJoin('c.size', 'cs')
            ->andWhere('c.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
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
     * @return Clothes[]
     */
    public function findVariantsBySlugs(array $slugs): array
    {
        $slugs = array_values(array_unique(array_filter($slugs)));

        if ($slugs === []) {
            return [];
        }

        return $this->createQueryBuilder('c')
            ->andWhere('c.slug IN (:slugs)')
            ->setParameter('slugs', $slugs)
            ->orderBy('c.slug', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Clothes[]
     */
    public function findBestsellerVariants(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isBestseller = true')
            ->orderBy('c.slug', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Clothes[]
     */
    public function findDistinctFeaturedEntities(): array
    {
        $results = $this->createQueryBuilder('c')
            ->addSelect('col')
            ->join('c.collection', 'col')
            ->andWhere('c.isInCarousel = true')
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
     * @return Clothes[]
     */
    public function findFeaturedVariants(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isInCarousel = true')
            ->orderBy('c.slug', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }


    /**
    * @return Clothes[] Returns an array of Clothes objects
    */
    public function findInCarouselDistinctBySlug(?int $limit): array
    {
        $qb = $this->createQueryBuilder('c');

        $qb ->select('c.slug, c.name, col.name AS collection , cat.name AS category')
            ->join('c.collection', 'col')
            ->join('col.category', 'cat')
            ->groupBy('c.slug, c.name, col.name, cat.name')
            ->orderBy('c.name',"asc")
            ->andWhere('c.isInCarousel = true')
        ;

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }


}
