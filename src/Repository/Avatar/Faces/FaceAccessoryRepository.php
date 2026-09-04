<?php

namespace App\Repository\Avatar\Faces;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Entity\Avatar\Faces\FaceAccessory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FaceAccessory>
 */
final class FaceAccessoryRepository extends ServiceEntityRepository implements AvatarFilterValueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FaceAccessory::class);
    }

    public function findOrCreate(string $name): FaceAccessory
    {
        $name = $this->normalizeName($name);

        if ('' === $name || 'none' === $name) {
            throw new \InvalidArgumentException('Invalid face accessory name.');
        }

        $accessory = $this->createQueryBuilder('a')
            ->andWhere('LOWER(a.name) = :name')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($accessory instanceof FaceAccessory) {
            return $accessory;
        }

        $accessory = (new FaceAccessory())
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setEditedAt(new \DateTimeImmutable());

        $this->getEntityManager()->persist($accessory);

        return $accessory;
    }

    private function normalizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $name = preg_replace('/[^a-z0-9_-]+/', '_', $name) ?? '';

        return trim($name, '_-');
    }
}
