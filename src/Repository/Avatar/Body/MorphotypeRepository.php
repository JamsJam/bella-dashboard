<?php

namespace App\Repository\Avatar\Body;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Body\Bodysize;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Skincolor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Morphotype>
 */
class MorphotypeRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Morphotype::class);
    }

    public function findOrCreate(string $name): Morphotype
    {
        $name = $this->normalizeName($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Invalid morphotype name.');
        }

        $morphotype = $this->findOneBy(['name' => $name]);
        if ($morphotype instanceof Morphotype) {
            return $morphotype;
        }

        throw new \InvalidArgumentException('Morphotype creation requires a morphologie and a body size.');
    }

    public function findOrCreateForRename(string $name, Morphologie $morphologie, Bodysize $size): Morphotype
    {
        $name = $this->normalizeName($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Invalid morphotype name.');
        }

        $morphotype = $this->findOneBy([
            'morphologie' => $morphologie,
            'size' => $size,
        ]);

        if ($morphotype instanceof Morphotype) {
            return $morphotype;
        }

        $morphotype = (new Morphotype())
            ->setName($name)
            ->setMorphologie($morphologie)
            ->setSize($size)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($morphotype);

        return $morphotype;
    }

    /** @return list<Morphotype> */
    public function findAvailableForSkinColorAndMorphologie(
        Skincolor $skinColor,
        Morphologie $morphologie,
    ): array {
        return $this->createQueryBuilder('morphotype')
            ->distinct()
            ->addSelect('size')
            ->join('morphotype.bodies', 'body')
            ->join('morphotype.size', 'size')
            ->andWhere('body.skincolor = :skinColor')
            ->andWhere('morphotype.morphologie = :morphologie')
            ->setParameter('skinColor', $skinColor)
            ->setParameter('morphologie', $morphologie)
            ->orderBy('size.name', 'ASC')
            ->addOrderBy('morphotype.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';

        return trim($name, '_-');
    }

    //    /**
    //     * @return Morphotype[] Returns an array of Morphotype objects
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

    //    public function findOneBySomeField($value): ?Morphotype
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
