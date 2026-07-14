<?php

namespace App\Repository\Clothes;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClothesVariant>
 */
class ClothesVariantRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClothesVariant::class);
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
        ?bool $online = null,
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

        if ($online === true) {
            $qb
                ->andWhere('category.isOnline = true')
                ->andWhere('collection.isOnline = true')
                ->andWhere('c.isOnline = true')
                ->andWhere('v.isOnline = true')
                ->andWhere('v.stock > 0');
        } elseif ($online === false) {
            $qb->andWhere($qb->expr()->orX(
                'category.isOnline = false',
                'collection.isOnline = false',
                'c.isOnline = false',
                'v.isOnline = false',
                'v.stock <= 0',
            ));
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
