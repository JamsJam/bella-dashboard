<?php

namespace App\Repository\Avatar\Body;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Skincolor;
use App\Entity\Avatar\Body\Morphologie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Morphologie>
 */
class MorphologieRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Morphologie::class);
    }

    public function findOrCreate(string $name): Morphologie
    {
        $name = $this->normalizeName($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Invalid morphologie name.');
        }

        $morphologie = $this->findOneByNormalizedName($name);
        if ($morphologie instanceof Morphologie) {
            return $morphologie;
        }

        $morphologie = (new Morphologie())
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($morphologie);

        return $morphologie;
    }

    /** @return list<Morphologie> */
    public function findAvailableForSkinColor(Skincolor $skinColor): array
    {
        return $this->createQueryBuilder('morphologie')
            ->distinct()
            ->join('morphologie.morphotypes', 'morphotype')
            ->join('morphotype.bodies', 'body')
            ->andWhere('body.skincolor = :skinColor')
            ->setParameter('skinColor', $skinColor)
            ->orderBy('morphologie.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function findOneByNormalizedName(string $name): ?Morphologie
    {
        $morphologie = $this->createQueryBuilder('m')
            ->andWhere('LOWER(m.name) = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $morphologie instanceof Morphologie ? $morphologie : null;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';

        return trim($name, '_-');
    }

    //    /**
    //     * @return Morphologie[] Returns an array of Morphologie objects
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

    //    public function findOneBySomeField($value): ?Morphologie
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
