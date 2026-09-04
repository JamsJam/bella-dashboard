<?php

namespace App\Repository\Avatar\Eyebrows;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Eyebrows\Eyebrowshape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Eyebrowshape>
 */
class EyebrowshapeRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eyebrowshape::class);
    }

    public function findOrCreate(string $name): Eyebrowshape
    {
        $name = $this->normalizeName($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Invalid eyebrow shape name.');
        }

        $shape = $this->findOneBy(['name' => $name]);
        if ($shape instanceof Eyebrowshape) {
            return $shape;
        }

        $shape = (new Eyebrowshape())
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($shape);

        return $shape;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';

        return trim($name, '_-');
    }

    //    /**
    //     * @return Eyebrowshape[] Returns an array of Eyebrowshape objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Eyebrowshape
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
