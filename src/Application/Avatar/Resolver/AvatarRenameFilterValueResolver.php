<?php

namespace App\Application\Avatar\Resolver;

use App\Application\Avatar\Interface\AvatarFilterValueRepositoryInterface;
use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use App\Entity\Avatar\Body\Bodysize;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Body\Morphotype;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AvatarRenameFilterValueResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvatarRenameFilterMapper $avatarRenameFilterMapper,
    ) {
    }

    public function resolve(string $sourceClass, string $part, string $filterId, mixed $value, array $filters = []): object
    {
        if (is_string($value) && ctype_digit($value)) {
            $entity = $this->entityManager->find($sourceClass, (int) $value);

            if (is_object($entity)) {
                return $entity;
            }

            throw new \InvalidArgumentException('Unknown avatar filter id.');
        }

        $rawValue = $this->extractName($value);
        $normalizedValue = $this->normalizeToken($rawValue);
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

        $entity = $repository->findOrCreate($normalizedValue);
        $hexa = $this->extractHexa($value);

        if ($hexa !== null && method_exists($entity, 'setHexa')) {
            $entity->setHexa($hexa);
        }

        return $entity;
    }

    public function resolveBodyMorphotype(array $filters): Morphotype
    {
        $morphologie = $this->resolveMorphologie($filters['morphologie'] ?? null);
        $size = $this->resolveBodySize($filters['bodySize'] ?? null);
        $name = $this->buildMorphotypeName($morphologie, $size);
        $repository = $this->entityManager->getRepository(Morphotype::class);

        if (method_exists($repository, 'findOrCreateForRename')) {
            return $repository->findOrCreateForRename($name, $morphologie, $size);
        }

        throw new \InvalidArgumentException('Repository for morphotypes cannot create avatar filter values.');
    }

    private function buildMorphotypeName(Morphologie $morphologie, Bodysize $size): string
    {
        return $this->normalizeToken(sprintf(
            '%s_%s',
            $morphologie->getName() ?? '',
            $size->getName() ?? '',
        ));
    }

    private function resolveMorphologie(mixed $value): Morphologie
    {
        if (is_string($value) && ctype_digit($value)) {
            $morphologie = $this->entityManager->find(Morphologie::class, (int) $value);

            if ($morphologie instanceof Morphologie) {
                return $morphologie;
            }
        }

        $repository = $this->entityManager->getRepository(Morphologie::class);
        if (!$repository instanceof AvatarFilterValueRepositoryInterface) {
            throw new \InvalidArgumentException('Repository for morphologies cannot create avatar filter values.');
        }

        $morphologie = $repository->findOrCreate($this->normalizeToken($this->extractName($value)));

        if (!$morphologie instanceof Morphologie) {
            throw new \InvalidArgumentException('Invalid morphologie filter value.');
        }

        return $morphologie;
    }

    private function resolveBodySize(mixed $value): Bodysize
    {
        if (is_string($value) && ctype_digit($value)) {
            $size = $this->entityManager->find(Bodysize::class, (int) $value);

            if ($size instanceof Bodysize) {
                return $size;
            }
        }

        $repository = $this->entityManager->getRepository(Bodysize::class);

        if (!$repository instanceof AvatarFilterValueRepositoryInterface) {
            throw new \InvalidArgumentException('Repository for body sizes cannot create avatar filter values.');
        }

        $size = $repository->findOrCreate(strtoupper($this->normalizeToken($this->extractName($value))));

        if (!$size instanceof Bodysize) {
            throw new \InvalidArgumentException('Invalid body size filter value.');
        }

        return $size;
    }

    private function extractName(mixed $value): string
    {
        if (is_array($value)) {
            return (string) ($value['name'] ?? '');
        }

        return (string) $value;
    }

    private function extractHexa(mixed $value): ?string
    {
        if (!is_array($value)) {
            return null;
        }

        $hexa = strtoupper(ltrim((string) ($value['hexa'] ?? ''), '#'));

        return preg_match('/^[0-9A-F]{6}$/', $hexa) === 1 ? $hexa : null;
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
