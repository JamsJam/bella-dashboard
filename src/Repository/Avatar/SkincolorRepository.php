<?php

namespace App\Repository\Avatar;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Skincolor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Skincolor>
 */
class SkincolorRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skincolor::class);
    }

    public function findOrCreate(string $name): Skincolor
    {
        $name = $this->normalizeName($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Invalid skin color name.');
        }

        $skincolor = $this->findOneByNormalizedName($name);
        if ($skincolor instanceof Skincolor) {
            return $skincolor;
        }

        $skincolor = (new Skincolor())
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($skincolor);

        return $skincolor;
    }

    private function findOneByNormalizedName(string $name): ?Skincolor
    {
        $skincolor = $this->createQueryBuilder('s')
            ->andWhere('LOWER(s.name) = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $skincolor instanceof Skincolor ? $skincolor : null;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';

        return trim($name, '_-');
    }

    //    /**
    //     * @return Skincolor[] Returns an array of Skincolor objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Skincolor
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
