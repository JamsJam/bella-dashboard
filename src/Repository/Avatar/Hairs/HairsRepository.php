<?php

namespace App\Repository\Avatar\Hairs;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
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

<<<<<<< HEAD
    /**
     * @return Hairs[] Returns an array of Hairs objects
     */
    public function findAllByFilters(
        ?int $color = null,
        ?int $shape = null,
    ): array {
        $qb = $this->createQueryBuilder('h');

        if (0 !== $color && null !== $color) {
            $qb->leftJoin('h.color', 'c')
                ->andWhere('c.id = :color')
                ->setParameter('color', $color);
        }

        if (0 !== $shape && null !== $shape) {
            $qb->leftJoin('h.shape', 's')
                ->andWhere('s.id = :shape')
                ->setParameter('shape', $shape);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function findPartByFilters(array $filters = []): array
    {
        return $this->findAllByFilters(
            $filters['color'] ?? null,
            $filters['shape'] ?? null
        );
    }

    public function findAllPart(): array
    {
        return $this->findAll();
    }

=======
>>>>>>> main
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
