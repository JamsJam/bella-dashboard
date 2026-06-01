<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use App\Application\Avatar\Resolver\AvatarRenameDestinationResolver;
use App\Application\Avatar\Resolver\AvatarRenameFilterValueResolver;
use App\Application\Avatar\Resolver\AvatarRenamePartResolver;
use App\Application\Avatar\Resolver\AvatarRenameSourcePathResolver;
use App\Entity\AvatarTemp;
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

        $avatarPart = $this->partResolver->resolvePart($message);
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
            if (!isset($message->filters[$filterId]) || trim((string) $message->filters[$filterId]) === '') {
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

            if ($sourceClass === null || $filterValue === '' || $filterValue === null) {
                continue;
            }

            $filterEntity = $this->filterValueResolver->resolve(
                sourceClass: $sourceClass,
                part: $message->category,
                filterId: (string) $filterId,
                value: (string) $filterValue,
            );
            $setter = $this->avatarRenameFilterMapper->getSetterForFilter((string) $filterId);

            if (!method_exists($avatarPart, $setter)) {
                if ($this->avatarRenameFilterMapper->isRequiredFilter($message->category, (string) $filterId)) {
                    throw new \RuntimeException(sprintf('Missing required setter "%s" on "%s".', $setter, $avatarPart::class));
                }

                continue;
            }

            $avatarPart->{$setter}($filterEntity);
        }
    }

    private function callRequiredSetter(object $entity, string $setter, mixed $value): void
    {
        if (!method_exists($entity, $setter)) {
            throw new \RuntimeException(sprintf('Missing setter "%s" on "%s".', $setter, $entity::class));
        }

        $entity->{$setter}($value);
    }
}
