<?php

namespace App\Repository\Avatar\Noses;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Noses\Nose;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Nose>
 */
class NoseRepository extends ServiceEntityRepository implements AvatarPartModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Nose::class);
    }

<<<<<<< HEAD
    /**
     * @return Nose[] Returns an array of Nose objects
     */
    public function findAllByFilters(
        ?int $skincolor = null,
        ?int $shape = null,
    ): array {
        $qb = $this->createQueryBuilder('n');

        if (0 !== $skincolor && null !== $skincolor) {
            $qb->leftJoin('n.skincolor', 'sc')
                ->andWhere('sc.id = :skincolor')
                ->setParameter('skincolor', $skincolor);
        }

        if (0 !== $shape && null !== $shape) {
            $qb->leftJoin('n.shape', 's')
                ->andWhere('s.id = :shape')
                ->setParameter('shape', $shape);
        }

        return $qb->getQuery()->getArrayResult();
    }

    public function findPartByFilters(array $filters = []): array
    {
        return $this->findAllByFilters(
            $filters['skinColor'] ?? null,
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
    //     * @return Nose[] Returns an array of Nose objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Nose
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
