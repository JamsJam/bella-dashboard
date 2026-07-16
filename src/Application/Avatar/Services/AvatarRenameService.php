<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use App\Application\Avatar\Resolver\AvatarRenameDestinationResolver;
use App\Application\Avatar\Resolver\AvatarRenameFilterValueResolver;
use App\Application\Avatar\Resolver\AvatarRenamePartResolver;
use App\Application\Avatar\Resolver\AvatarRenameSourcePathResolver;
use App\Entity\Avatar\Body\Body;
use App\Entity\AvatarTemp;
use App\Entity\Clothes\Clothes;
use App\Message\Avatar\RenameAvatarMessage;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AvatarRenameService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvatarRenameFilterMapper $avatarRenameFilterMapper,
        private AvatarRenameSourcePathResolver $sourcePathResolver,
        private AvatarRenameDestinationResolver $destinationResolver,
        private AvatarRenamePartResolver $partResolver,
        private AvatarRenameFilterValueResolver $filterValueResolver,
    ) {
    }

    public function renameFromMessage(RenameAvatarMessage $message): void
    {
        $avatarTemp = $this->entityManager->find(AvatarTemp::class, $message->avatarTempId);

        if (!$avatarTemp instanceof AvatarTemp) {
            return;
        }

        try {
            $this->process($avatarTemp, $message);
        } catch (\Throwable) {
            $avatarTemp->setStatus('error');
            $this->entityManager->flush();
        }
    }

    private function process(AvatarTemp $avatarTemp, RenameAvatarMessage $message): void
    {
        $this->assertSafeNewName($message->newName);
        $this->assertRequiredFilters($message);

        $sourcePath = $this->sourcePathResolver->resolve($avatarTemp);
        $destinationWebDir = $this->destinationResolver->resolveWebDirectory($message);
        $destinationDir = $this->destinationResolver->resolveAbsoluteDirectory($destinationWebDir);

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
            throw new \RuntimeException('Unable to create final avatar directory.');
        }

        $destinationPath = $destinationDir.'/'.$message->newName;
        $this->destinationResolver->assertDestinationPathIsAllowed($destinationPath);

        if (file_exists($destinationPath) && !$message->replaceExisting) {
            throw new \RuntimeException('Avatar final filename collision.');
        }

        $avatarPart = $this->resolveAvatarPart($message);
        $this->hydrateAvatarPart($avatarPart, $message, $sourcePath, $destinationWebDir.'/'.$message->newName);

        if (file_exists($destinationPath) && $message->replaceExisting && !unlink($destinationPath)) {
            throw new \RuntimeException('Unable to replace existing avatar file.');
        }

        if (!rename($sourcePath, $destinationPath)) {
            throw new \RuntimeException('Unable to rename avatar file.');
        }
        @rmdir(dirname($sourcePath));

        $avatarTemp->setFinalName($message->newName);
        $avatarTemp->setStatus('renamed');

        $this->entityManager->persist($avatarPart);
        $this->entityManager->remove($avatarTemp);
        $this->entityManager->flush();
    }

    private function resolveAvatarPart(RenameAvatarMessage $message): object
    {
        if ($message->category !== 'body') {
            return $this->partResolver->resolvePart($message);
        }

        $body = new Body();
        $clothes = $this->resolveClothesForBody($message);
        foreach ($clothes as $clothe) {
            $body->addClothe($clothe);
        }

        return $body;
    }

    /**
     * @return list<Clothes>
     */
    private function resolveClothesForBody(RenameAvatarMessage $message): array
    {
        $value = $message->filters['clothes'] ?? null;
        $slug = $this->resolveClothesSlug($value);

        if ($slug === '') {
            return [];
        }

        $repository = $this->entityManager->getRepository(Clothes::class);
        $clothe = $repository->findOneBy(['slug' => $slug]);

        return $clothe instanceof Clothes ? [$clothe] : [];
    }

    private function resolveClothesSlug(mixed $value): string
    {
        $value = $this->extractFilterName($value);

        if ($value === '') {
            return '';
        }

        if (ctype_digit($value)) {
            $clothe = $this->entityManager->find(Clothes::class, (int) $value);

            return $clothe instanceof Clothes ? (string) $clothe->getSlug() : '';
        }

        return $value;
    }

    private function assertSafeNewName(string $newName): void
    {
        if (
            !preg_match('/^[A-Za-z0-9_-]+\.png$/', $newName)
            || str_contains($newName, '/')
            || str_contains($newName, '\\')
            || str_contains($newName, '..')
        ) {
            throw new \InvalidArgumentException('Unsafe avatar filename.');
        }
    }

    private function assertRequiredFilters(RenameAvatarMessage $message): void
    {
        foreach ($this->avatarRenameFilterMapper->getRequiredFilters($message->category) as $filterId) {
            if (!isset($message->filters[$filterId]) || $this->extractFilterName($message->filters[$filterId]) === '') {
                throw new \InvalidArgumentException(sprintf('Missing required avatar filter "%s".', $filterId));
            }
        }
    }

    private function hydrateAvatarPart(object $avatarPart, RenameAvatarMessage $message, string $checksumPath, string $imagePath): void
    {
        $this->callRequiredSetter($avatarPart, 'setName', $this->partResolver->resolveName($message));
        $this->callRequiredSetter($avatarPart, 'setChecksum', hash_file('sha256', $checksumPath));

        if (method_exists($avatarPart, 'setImage')) {
            $avatarPart->setImage($imagePath);
        } elseif (method_exists($avatarPart, 'setImages')) {
            $avatarPart->setImages($this->partResolver->resolveImagesPayload($avatarPart, $message, $imagePath));
        }

        if (method_exists($avatarPart, 'setCreatedAt')) {
            $avatarPart->setCreatedAt(new \DateTimeImmutable());
        }

        if (method_exists($avatarPart, 'setEditedAt')) {
            $avatarPart->setEditedAt(new \DateTimeImmutable());
        }

        $this->hydrateAvatarFilters($avatarPart, $message);
    }

    private function hydrateAvatarFilters(object $avatarPart, RenameAvatarMessage $message): void
    {
        foreach ($message->filters as $filterId => $filterValue) {
            $sourceClass = $this->avatarRenameFilterMapper->getFilterSourceClass($message->category, (string) $filterId);

            if ($sourceClass === null || $filterValue === null || $this->extractFilterName($filterValue) === '') {
                continue;
            }

            if ($message->category === 'body' && (string) $filterId === 'clothes' && $avatarPart instanceof Body) {
                continue;
            }

            if ($message->category === 'face' && (string) $filterId === 'accessory' && $this->extractFilterName($filterValue) === '-none-') {
                continue;
            }

            $filterEntity = $this->filterValueResolver->resolve(
                sourceClass: $sourceClass,
                part: $message->category,
                filterId: (string) $filterId,
                value: $filterValue,
                filters: $message->filters,
            );
            $setter = $this->avatarRenameFilterMapper->getSetterForFilter((string) $filterId);

            if ($this->isContextFilter((string) $filterId)) {
                continue;
            }

            if (!method_exists($avatarPart, $setter)) {
                if ($this->avatarRenameFilterMapper->isRequiredFilter($message->category, (string) $filterId)) {
                    throw new \RuntimeException(sprintf('Missing required setter "%s" on "%s".', $setter, $avatarPart::class));
                }

                continue;
            }

            $avatarPart->{$setter}($filterEntity);
        }

        if ($message->category === 'body' && $avatarPart instanceof Body) {
            $avatarPart->setMorphotype($this->filterValueResolver->resolveBodyMorphotype($message->filters));
        }
    }

    private function callRequiredSetter(object $entity, string $setter, mixed $value): void
    {
        if (!method_exists($entity, $setter)) {
            throw new \RuntimeException(sprintf('Missing setter "%s" on "%s".', $setter, $entity::class));
        }

        $entity->{$setter}($value);
    }

    private function extractFilterName(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['name'] ?? '';
        }

        return trim((string) $value);
    }

    private function isContextFilter(string $filterId): bool
    {
        return in_array($filterId, ['morphologie', 'bodySize'], true);
    }
}
