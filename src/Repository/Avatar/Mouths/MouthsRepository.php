<?php

namespace App\Repository\Avatar\Mouths;

use App\Entity\Avatar\Mouths\Mouths;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mouths>
 */
class MouthsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mouths::class);
    }

    /**
     * @return Eyebrows[] Returns an array of Body objects
     */
    public function findAllByFilters(
        array $color = [],
        array $shape = [],
    ): array {
        $qb = $this->createQueryBuilder('b');

        if (!empty($color)) {
            $qb->leftJoin('b.color', 'c');
            $or = $qb->expr()->orX();
            foreach ($color as $i => $id) {
                $param = 'color_'.$i;
                $or->add($qb->expr()->eq('c.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if (!empty($shape)) {
            $qb->leftJoin('b.shape', 's');
            $or = $qb->expr()->orX();
            foreach ($shape as $i => $id) {
                $param = 'shape_'.$i;
                $or->add($qb->expr()->eq('s.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        return $qb->getQuery()->getArrayResult();
    }

    //    /**
    //     * @return Mouths[] Returns an array of Mouths objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Mouths
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
