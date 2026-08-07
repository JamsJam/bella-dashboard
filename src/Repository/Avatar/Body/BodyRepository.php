<?php

namespace App\Repository\Avatar\Body;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Skincolor;
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

    /**
     * @return list<Body>
     */
    public function findForAvatarSelection(
        Skincolor $skinColor,
        Morphotype $morphotype,
        int|string|null $clothes = null,
    ): array {
        $queryBuilder = $this->createQueryBuilder('body')
            ->andWhere('body.skincolor = :skinColor')
            ->andWhere('body.morphotype = :morphotype')
            ->setParameter('skinColor', $skinColor)
            ->setParameter('morphotype', $morphotype)
            ->orderBy('body.name', 'ASC');

        if (null !== $clothes) {
            $queryBuilder
                ->distinct()
                ->innerJoin('body.clothesVariants', 'clothesVariant');

            if (is_int($clothes)) {
                $queryBuilder
                    ->andWhere('clothesVariant.id = :clothes')
                    ->setParameter('clothes', $clothes);
            } else {
                $queryBuilder
                    ->andWhere('clothesVariant.slug = :clothes')
                    ->setParameter('clothes', $clothes);
            }
        }

        return $queryBuilder->getQuery()->getResult();
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
     * @return Body[] Returns an array of Body objects
     */
    public function findAllByFilters(
        ?int $skincolor = null,
        ?int $morphologie = null,
        ?int $morphotype = null,
        int|string|null $clothes = null,
        // ?int $collection = null,
    ): array {
        $qb = $this->createQueryBuilder('b');

        if (0 !== $skincolor && null !== $skincolor) {
            $qb->leftJoin('b.skincolor', 'sc')
                ->andWhere('sc.id = :skincolor')
                ->setParameter('skincolor', $skincolor);
        }

        if (0 !== $morphotype && null !== $morphotype) {
            $qb->leftJoin('b.morphotype', 'mt')
                ->andWhere('mt.id = :morphotype')
                ->setParameter('morphotype', $morphotype);
        }

        if (0 !== $morphologie && null !== $morphologie) {
            // Si le morphotype n'est pas spécifié, on doit faire une jointure pour accéder à la morphologie
            if (null === $morphotype || 0 === $morphotype) {
                $qb->leftJoin('b.morphotype', 'mt');
            }

            $qb->leftJoin('mt.morphologie', 'ml')
                ->andWhere('ml.id = :morphologie')
                ->setParameter('morphologie', $morphologie);
        }

        if (0 !== $clothes && null !== $clothes && '' !== $clothes && '0' !== $clothes) {
            $qb->distinct();
            $qb->leftJoin('b.clothes', 'cl');

            if (is_numeric($clothes)) {
                $qb
                    ->andWhere('cl.id = :clothes')
                    ->setParameter('clothes', (int) $clothes);
            } else {
                $qb
                    ->andWhere('cl.slug = :clothes')
                    ->setParameter('clothes', (string) $clothes);
            }
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
