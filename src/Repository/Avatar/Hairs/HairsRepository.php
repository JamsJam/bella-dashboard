<?php

namespace App\Repository\Avatar\Hairs;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Eyebrows\Eyebrows;
use App\Entity\Avatar\Hairs\Hairs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hairs>
 */
class HairsRepository extends ServiceEntityRepository implements AvatarPartModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hairs::class);
    }

    /**
     * @return Hairs[] Returns an array of Hairs objects
     */
    public function findAllByFilters(
        ?int $color = null,
        ?int $shape = null,
    ): array {
        $qb = $this->createQueryBuilder('h');

        if ($color !== 0 && $color !== null) {
            $qb->leftJoin('h.color', 'c')
                ->andWhere('c.id = :color')
                ->setParameter('color', $color);
        }

        if ($shape !== 0 && $shape !== null) {
            $qb->leftJoin('h.shape', 's')
                ->andWhere('s.id = :shape')
                ->setParameter('shape', $shape);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findPartByFilters(array $filters = []): array
    {
        return $this->findAllByFilters(
            $filters['color'] ?? null,
            $filters['shape'] ?? null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findAllPart(): array
    {
        return $this->findAll();
    }

    //    /**
    //     * @return Hairs[] Returns an array of Hairs objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('h.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Hairs
    //    {
    //        return $this->createQueryBuilder('h')
    //            ->andWhere('h.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
