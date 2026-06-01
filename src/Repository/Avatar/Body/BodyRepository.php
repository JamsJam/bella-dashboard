<?php

namespace App\Repository\Avatar\Body;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Body\Body;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Body>
 */
class BodyRepository extends ServiceEntityRepository implements AvatarPartModelInterface
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
     *  @return Body[] Returns an array of Body objects
     *
     */      
    public function findAllByFilters(
        ?int $skincolor = null,
        ?int $morphologie = null,
        ?int $morphotype = null,
        ?int $clothes = null,
        // ?int $collection = null,
    ): array {
        $qb = $this->createQueryBuilder('b');

        if ($skincolor !== 0 && $skincolor !== null) {
            $qb->leftJoin('b.skincolor', 'sc')
                ->andWhere('sc.id = :skincolor')
                ->setParameter('skincolor', $skincolor);
        }

        if ($morphotype !== 0 && $morphotype !== null) {
            $qb->leftJoin('b.morphotype', 'mt')
                ->andWhere('mt.id = :morphotype')
                ->setParameter('morphotype', $morphotype);
        }


        if ($morphologie !== 0 && $morphologie !== null) {
            // Si le morphotype n'est pas spécifié, on doit faire une jointure pour accéder à la morphologie
            if ($morphotype === null || $morphotype === 0) {
                $qb->leftJoin('b.morphotype', 'mt');
            }

            $qb->leftJoin('mt.morphologie', 'ml')
                ->andWhere('ml.id = :morphologie')
                ->setParameter('morphologie', $morphologie);

        }

        if ($clothes !== 0 && $clothes !== null) {
            $qb->leftJoin('b.clothe', 'cl')
                ->andWhere('cl.id = :clothes')
                ->setParameter('clothes', $clothes);
        }

        // if ($collection !== 0 && $collection !== null) {
        //     if (empty($clothes)) {
        //         $qb->leftJoin('b.clothe', 'cl');
        //     }
        //     $qb->leftJoin('cl.collection', 'co');
        //     $or = $qb->expr()->orX();
        //     foreach ($collection as $i => $id) {
        //         $param = 'collection_'.$i;          // nom unique : skincolor_0, _1, …
        //         $or->add($qb->expr()->eq('co.id', ':'.$param));
        //         $qb->setParameter($param, $id);
        //     }
        //     $qb->andWhere($or);
        // }



        return
        $qb
            ->getQuery()
            ->getArrayResult()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function findPartByFilters(array $filters = []): array
    {
        return $this->findAllByFilters(
            $filters['skinColor'] ?? null,
            $filters['morphologie'] ?? null,
            $filters['morphotype'] ?? null,
            $filters['clothes'] ?? null,
            // $filters['collection'] ?? []
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findAllPart(): array
    {
        return $this->findAll();
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
