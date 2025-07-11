<?php

namespace App\Repository\Avatar\Body;

use App\Entity\Avatar\Body\Body;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Body>
 */
class BodyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Body::class);
    }

    //    /**
    //     * @return Body[] Returns an array of Body objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('b.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * //     * @return Body[] Returns an array of Body objects
     * //     */
    public function findAllByFilters(
        array $skincolor = [],
        array $morphologie = [],
        array $morphotype = [],
        array $clothes = [],
        array $collection = [],
    ): array {
        $qb = $this->createQueryBuilder('b');

        if (!empty($skincolor)) {
            $qb->leftJoin('b.skincolor', 'sc');
            $or = $qb->expr()->orX();
            foreach ($skincolor as $i => $id) {
                $param = 'skincolor_'.$i;          // nom unique : skincolor_0, _1, …
                $or->add($qb->expr()->eq('sc.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if (!empty($morphotype)) {
            $qb->leftJoin('b.morphotype', 'mt');
            $qb->leftJoin('mt.size', 'bs');

            $or = $qb->expr()->orX();
            foreach ($morphotype as $i => $sizeName) {
                $param = 'morphotype_'.$i;          // nom unique : skincolor_0, _1, …
                $or->add($qb->expr()->eq('bs.name', ':'.$param));
                $qb->setParameter($param, $sizeName);

                $qb->andWhere($or);
            }
        }

        if (!empty($morphologie)) {
            if (empty($morphotype)) {
                $qb->leftJoin('b.morphotype', 'mt');
            }
            $qb->leftJoin('mt.morphologie', 'ml');
            $or = $qb->expr()->orX();
            foreach ($morphologie as $i => $id) {
                $param = 'morphologie_'.$i;          // nom unique : skincolor_0, _1, …
                $or->add($qb->expr()->eq('ml.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if (!empty($clothes)) {
            $qb->leftJoin('b.clothe', 'cl');
            $or = $qb->expr()->orX();
            foreach ($clothes as $i => $id) {
                $param = 'clothe_'.$i;          // nom unique : skincolor_0, _1, …
                $or->add($qb->expr()->eq('cl.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        if (!empty($collection)) {
            if (empty($clothes)) {
                $qb->leftJoin('b.clothe', 'cl');
            }
            $qb->leftJoin('cl.collection', 'co');
            $or = $qb->expr()->orX();
            foreach ($collection as $i => $id) {
                $param = 'collection_'.$i;          // nom unique : skincolor_0, _1, …
                $or->add($qb->expr()->eq('co.id', ':'.$param));
                $qb->setParameter($param, $id);

                $qb->andWhere($or);
            }
        }

        /* mêmes tests pour les autres filtres … */

        return
        $qb
            ->getQuery()
            // ->getResult()
            ->getArrayResult()
        ;
    }

    //    public function findOneBySomeField($value): ?Body
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
