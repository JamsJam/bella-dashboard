<?php

namespace App\Repository\Avatar\Hairs;

use App\Entity\Avatar\Hairs\Hairs;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hairs>
 */
class HairsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hairs::class);
    }

    /**
     * @return Eyebrows[] Returns an array of Body objects
     */
    public function findAllByFilters(
        array $color = [],
        array $shape = [],
    ): array {
        $qb = $this->createQueryBuilder('h');

        if (!empty($color)) {
            $qb->leftJoin('h.color', 'c');
            $or = $qb->expr()->orX();
            foreach ($color as $i => $id) {
                $param = 'color_'.$i;
                $or->add($qb->expr()->eq('c.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if (!empty($shape)) {
            $qb->leftJoin('h.shape', 's');
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
