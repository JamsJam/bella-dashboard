<?php

namespace App\Repository\Avatar\Body;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Body\Morphologie;
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

    /** @return list<Body> */
    public function findByClothesSlug(string $clothes): array
    {
        $queryBuilder = $this->createQueryBuilder('body')
            ->orderBy('body.name', 'ASC');

        if ('none' === strtolower($clothes)) {
            $queryBuilder
                ->leftJoin('body.clothesVariants', 'clothesVariant')
                ->andWhere('clothesVariant.id IS NULL');
        } else {
            $queryBuilder
                ->distinct()
                ->innerJoin('body.clothesVariants', 'clothesVariant')
                ->andWhere('clothesVariant.slug = :clothes')
                ->setParameter('clothes', $clothes);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findPreviewForMorphology(Skincolor $skinColor, Morphologie $morphology): ?Body
    {
        return $this->findPreview($skinColor, null, $morphology);
    }

    public function findPreviewForMorphotype(Skincolor $skinColor, Morphotype $morphotype): ?Body
    {
        return $this->findPreview($skinColor, $morphotype, null);
    }

    private function findPreview(
        Skincolor $skinColor,
        ?Morphotype $morphotype,
        ?Morphologie $morphology,
    ): ?Body {
        $queryBuilder = $this->createQueryBuilder('body')
            ->leftJoin('body.clothesVariants', 'clothesVariant')
            ->andWhere('body.skincolor = :skinColor')
            ->andWhere('clothesVariant.id IS NULL')
            ->setParameter('skinColor', $skinColor)
            ->orderBy('body.name', 'ASC')
            ->setMaxResults(1);

        if ($morphotype instanceof Morphotype) {
            $queryBuilder
                ->andWhere('body.morphotype = :morphotype')
                ->setParameter('morphotype', $morphotype);
        } elseif ($morphology instanceof Morphologie) {
            $queryBuilder
                ->join('body.morphotype', 'previewMorphotype')
                ->andWhere('previewMorphotype.morphologie = :morphology')
                ->setParameter('morphology', $morphology);
        }

        $body = $queryBuilder->getQuery()->getOneOrNullResult();

        if (!$body instanceof Body) {
            $fallbackCriteria = ['skincolor' => $skinColor];
            if ($morphotype instanceof Morphotype) {
                $fallbackCriteria['morphotype'] = $morphotype;
            } elseif ($morphology instanceof Morphologie) {
                $fallback = $this->createQueryBuilder('body')
                    ->join('body.morphotype', 'fallbackMorphotype')
                    ->andWhere('body.skincolor = :skinColor')
                    ->andWhere('fallbackMorphotype.morphologie = :morphology')
                    ->setParameter('skinColor', $skinColor)
                    ->setParameter('morphology', $morphology)
                    ->orderBy('body.name', 'ASC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                return $fallback instanceof Body ? $fallback : null;
            }

            $body = $this->findOneBy($fallbackCriteria, ['name' => 'ASC']);
        }

        return $body instanceof Body ? $body : null;
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

<<<<<<< HEAD
    /**
     * @return Body[] Returns an array of Body objects
     */
    public function findAllByFilters(
        ?int $skincolor = null,
        ?int $morphologie = null,
        ?int $bodySize = null,
        ?int $morphotype = null,
        int|string|null $clothes = null,
        ?int $collection = null,
    ): array {
        $qb = $this->createQueryBuilder('b');

        if (0 !== $skincolor && null !== $skincolor) {
            $qb->leftJoin('b.skincolor', 'sc')
                ->andWhere('sc.id = :skincolor')
                ->setParameter('skincolor', $skincolor);
        }

        if ((0 !== $morphotype && null !== $morphotype)
            || (0 !== $morphologie && null !== $morphologie)
            || (0 !== $bodySize && null !== $bodySize)) {
            $qb->leftJoin('b.morphotype', 'mt')
            ;
        }

        if (0 !== $morphotype && null !== $morphotype) {
            $qb->andWhere('mt.id = :morphotype')
                ->setParameter('morphotype', $morphotype)
            ;
        }

        if (0 !== $morphologie && null !== $morphologie) {
            $qb->leftJoin('mt.morphologie', 'ml')
                ->andWhere('ml.id = :morphologie')
                ->setParameter('morphologie', $morphologie);
        }

        if (0 !== $bodySize && null !== $bodySize) {
            $qb->leftJoin('mt.size', 'bs')
                ->andWhere('bs.id = :bodySize')
                ->setParameter('bodySize', $bodySize);
        }

        $hasClothes = 0 !== $clothes && null !== $clothes && '' !== $clothes && '0' !== $clothes;
        $hasCollection = 0 !== $collection && null !== $collection;

        if ($hasClothes || $hasCollection) {
            $qb->distinct()
                ->innerJoin('b.clothesVariants', 'cv')
                ->innerJoin('cv.clothes', 'cl');
        }

        if ($hasClothes) {
            if (is_numeric($clothes)) {
                $qb
                    ->andWhere('cl.id = :clothes')
                    ->setParameter('clothes', (int) $clothes);
            } else {
                $qb
                    ->andWhere('cv.slug = :clothes')
                    ->setParameter('clothes', (string) $clothes);
            }
        }

        if ($hasCollection) {
            $qb->innerJoin('cl.collection', 'co')
                ->andWhere('co.id = :collection')
                ->setParameter('collection', $collection);
        }

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
            $filters['bodySize'] ?? null,
            $filters['morphotype'] ?? null,
            $filters['clothes'] ?? null,
            $filters['collection'] ?? null,
        );
    }

    public function findAllPart(): array
    {
        return $this->findAll();
    }

=======
>>>>>>> main
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
