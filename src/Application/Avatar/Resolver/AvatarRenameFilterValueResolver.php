<?php

namespace App\Application\Avatar\Resolver;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AvatarRenameFilterValueResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvatarRenameFilterMapper $avatarRenameFilterMapper,
    ) {
    }

    public function resolve(string $sourceClass, string $part, string $filterId, string $value): object
    {
        if (ctype_digit($value)) {
            $entity = $this->entityManager->find($sourceClass, (int) $value);

            if (is_object($entity)) {
                return $entity;
            }

            throw new \InvalidArgumentException('Unknown avatar filter id.');
        }

        $normalizedValue = $this->normalizeToken($value);
        if ($normalizedValue === '') {
            throw new \InvalidArgumentException('Invalid avatar filter value.');
        }

        if (!$this->avatarRenameFilterMapper->isCreatableFilter($part, $filterId)) {
            throw new \InvalidArgumentException(sprintf('Unsupported custom avatar filter "%s" for "%s".', $filterId, $part));
        }

        $repository = $this->entityManager->getRepository($sourceClass);
        if (!$repository instanceof AvatarFilterValueRepositoryInterface) {
            throw new \InvalidArgumentException(sprintf('Repository for "%s" cannot create avatar filter values.', $sourceClass));
        }

        return $repository->findOrCreate($normalizedValue);
    }

    private function normalizeToken(string $value): string
    {
        $value = strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/\s+/', '--', $value) ?? '';
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';
        $value = preg_replace('/_+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}
