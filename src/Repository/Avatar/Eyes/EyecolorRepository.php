<?php

namespace App\Repository\Avatar\Eyes;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Eyes\Eyecolor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Eyecolor>
 */
class EyecolorRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Eyecolor::class);
    }

    public function findOrCreate(string $name): Eyecolor
    {
        $name = $this->normalizeName($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Invalid eye color name.');
        }

        $color = $this->findOneBy(['name' => $name]);
        if ($color instanceof Eyecolor) {
            return $color;
        }

        $color = (new Eyecolor())
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($color);

        return $color;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';

        return trim($name, '_-');
    }

    //    /**
    //     * @return Eyecolor[] Returns an array of Eyecolor objects
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

    //    public function findOneBySomeField($value): ?Eyecolor
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
