<?php

namespace App\Repository\Avatar\Faces;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Faces\Faceshape;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faceshape>
 */
class FaceshapeRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faceshape::class);
    }

    public function findOrCreate(string $name): Faceshape
    {
        $name = $this->normalizeName($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Invalid face shape name.');
        }

        $shape = $this->findOneBy(['name' => $name]);
        if ($shape instanceof Faceshape) {
            return $shape;
        }

        $shape = (new Faceshape())
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
    //     * @return Faceshape[] Returns an array of Faceshape objects
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

    //    public function findOneBySomeField($value): ?Faceshape
    //    {
    //        return $this->createQueryBuilder('f')
    //            ->andWhere('f.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
