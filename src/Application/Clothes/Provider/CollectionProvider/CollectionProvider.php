<?php

namespace App\Application\Clothes\Provider\CollectionProvider;

use App\Entity\Category\Category;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Collections\Collections;
use Doctrine\ORM\EntityManagerInterface;

final readonly class CollectionProvider
{
    private const SORTS = [
        'id' => 'col.id',
        'name' => 'col.name',
        'category' => 'cat.name',
        'isOnline' => 'col.isOnline',
        'clothesCount' => 'clothesCount',
        'createdAt' => 'col.createdAt',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @return list<array{0: Collections, clothesCount: string|int}> */
    public function search(string $search, string $sort, string $direction): array
    {
        $sort = array_key_exists($sort, self::SORTS) ? $sort : 'name';
        $direction = 'desc' === strtolower($direction) ? 'desc' : 'asc';
        $queryBuilder = $this->entityManager->getRepository(Collections::class)
            ->createQueryBuilder('col')
            ->select('col, cat, COUNT(cl.id) AS clothesCount')
            ->leftJoin('col.category', 'cat')
            ->leftJoin('col.clothes', 'cl')
            ->groupBy('col.id')
            ->addGroupBy('cat.id')
            ->orderBy(self::SORTS[$sort], $direction);

        if ('' !== $search) {
            $queryBuilder
                ->andWhere('LOWER(col.name) LIKE :search OR LOWER(cat.name) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        return $queryBuilder->setMaxResults(100)->getQuery()->getResult();
    }

    /** @return list<Category> */
    public function categories(): array
    {
        return $this->entityManager->getRepository(Category::class)->findBy([], ['name' => 'ASC']);
    }

    /** @return list<Clothescolor> */
    public function colors(): array
    {
        return $this->entityManager->getRepository(Clothescolor::class)->findBy([], ['name' => 'ASC']);
    }
}
