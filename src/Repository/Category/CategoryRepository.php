<?php

namespace App\Repository\Category;

use App\Entity\Category\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 */
class CategoryRepository extends ServiceEntityRepository
{
    private const SORTS = [
        'id' => 'category.id',
        'name' => 'category.name',
        'slug' => 'category.slug',
        'isOnline' => 'category.isOnline',
        'collectionsCount' => 'collectionsCount',
        'createdAt' => 'category.createdAt',
    ];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @return list<Category>
     */
    public function findOnlineForPage(): array
    {
        return $this->createQueryBuilder('category')
            ->andWhere('category.isOnline = true')
            ->orderBy('category.name', 'ASC')
            ->addOrderBy('category.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<array{0: Category, collectionsCount: int|string}>
     */
    public function searchWithCollectionsCount(
        string $search,
        string $sort,
        string $direction,
        int $limit = 100,
    ): array {
        $sort = array_key_exists($sort, self::SORTS) ? $sort : 'name';
        $direction = 'desc' === strtolower($direction) ? 'desc' : 'asc';

        $queryBuilder = $this->createQueryBuilder('category')
            ->select('category, COUNT(collection.id) AS collectionsCount')
            ->leftJoin('category.collections', 'collection')
            ->groupBy('category.id')
            ->orderBy(self::SORTS[$sort], $direction)
            ->setMaxResults($limit);

        if ('' !== $search) {
            $queryBuilder
                ->andWhere(
                    'LOWER(category.name) LIKE :search '
                    . 'OR LOWER(category.slug) LIKE :search',
                )
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function slugExists(string $slug, ?Category $excludedCategory = null): bool
    {
        $category = $this->findOneBy(['slug' => $slug]);

        return $category instanceof Category
            && $category->getId() !== $excludedCategory?->getId();
    }

    //    /**
    //     * @return Category[] Returns an array of Category objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Category
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
