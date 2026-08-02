<?php

namespace App\Repository\Avatar\Faces;

use App\Application\Avatar\Interface\AvatarPartModelInterface;
use App\Entity\Avatar\Faces\Faces;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faces>
 */
class FacesRepository extends ServiceEntityRepository implements AvatarPartModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faces::class);
    }

    /**
     * @return Faces[] Returns an array of Faces objects
     */
    public function findAllByFilters(
        ?int $skincolor = null,
        ?int $shape = null,
        int|string|null $accessory = null,
    ): array {
        $qb = $this->createQueryBuilder('f');

        if ($skincolor !== 0 && $skincolor !== null) {
            $qb->leftJoin('f.skincolor', 'sc')
                ->andWhere('sc.id = :skincolor')
                ->setParameter('skincolor', $skincolor);
        }

        if ($shape !== 0 && $shape !== null) {
            $qb->leftJoin('f.shape', 's')
                ->andWhere('s.id = :shape')
                ->setParameter('shape', $shape);
        }

        if ($accessory === '-none-') {
            $qb->andWhere('f.accessory IS NULL');
        } elseif ($accessory !== 0 && $accessory !== null && $accessory !== '') {
            $qb->leftJoin('f.accessory', 'a')
                ->andWhere('a.id = :accessory')
                ->setParameter('accessory', (int) $accessory);
        }

        return $qb->getQuery()->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findPartByFilters(array $filters = []): array
    {
        return $this->findAllByFilters(
            $filters['skinColor'] ?? null,
            $filters['shape'] ?? null,
            $filters['accessory'] ?? null,
        );
    }

    /**
     * @return Faces[]
     */
    public function findAccessorizedFor(Faces $face): array
    {
        if ($face->getSkincolor() === null || $face->getShape() === null) {
            return [];
        }

        return $this->createQueryBuilder('f')
            ->innerJoin('f.accessory', 'a')
            ->andWhere('f.skincolor = :skincolor')
            ->andWhere('f.shape = :shape')
            ->setParameter('skincolor', $face->getSkincolor())
            ->setParameter('shape', $face->getShape())
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Faces[]
     */
    public function findByNamePrefix(string $prefix): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.name LIKE :prefix')
            ->setParameter('prefix', $prefix.'%')
            ->orderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findAllPart(): array
    {
        return $this->findAll();
    }

    //    /**
    //     * @return Faces[] Returns an array of Faces objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('f.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Faces
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
