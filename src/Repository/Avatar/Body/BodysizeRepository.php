<?php

namespace App\Repository\Avatar\Body;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Body\Bodysize;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bodysize>
 */
class BodysizeRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bodysize::class);
    }

    public function findOrCreate(string $name): Bodysize
    {
        $name = $this->normalizeName($name);

        if ($name === '') {
            throw new \InvalidArgumentException('Invalid body size name.');
        }

        $size = $this->findOneByNormalizedName($name);
        if ($size instanceof Bodysize) {
            return $size;
        }

        $size = (new Bodysize())
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($size);

        return $size;
    }

    private function findOneByNormalizedName(string $name): ?Bodysize
    {
        $size = $this->createQueryBuilder('b')
            ->andWhere('UPPER(b.name) = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $size instanceof Bodysize ? $size : null;
    }

    private function normalizeName(string $name): string
    {
        $name = strtoupper(trim($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^A-Z0-9_-]+/', '_', $name) ?? '';

        return substr(trim($name, '_-'), 0, 5);
    }

    //    /**
    //     * @return Bodysize[] Returns an array of Bodysize objects
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

    //    public function findOneBySomeField($value): ?Bodysize
    //    {
    //        return $this->createQueryBuilder('b')
    //            ->andWhere('b.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
