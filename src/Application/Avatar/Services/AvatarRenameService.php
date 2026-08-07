<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use App\Application\Avatar\Model\AvatarRenameInstruction;
use App\Application\Avatar\Resolver\AvatarRenameDestinationResolver;
use App\Application\Avatar\Resolver\AvatarRenameFilterValueResolver;
use App\Application\Avatar\Resolver\AvatarRenamePartResolver;
use App\Application\Avatar\Resolver\AvatarRenameSourcePathResolver;
use App\Application\Avatar\Workflow\AvatarRenameCompletionContext;
use App\Application\Avatar\Workflow\AvatarRenameGuardContextStore;
use App\Application\Avatar\Workflow\AvatarRenameWorkflow;
use App\Entity\Avatar\Body\Body;
use App\Entity\AvatarTemp;
use App\Entity\Clothes\Clothes;
use App\Message\Avatar\RenameAvatarMessage;
use App\Repository\Clothes\ClothesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Workflow\WorkflowInterface;

final readonly class AvatarRenameService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvatarRenameFilterMapper $avatarRenameFilterMapper,
        private AvatarRenameSourcePathResolver $sourcePathResolver,
        private AvatarRenameDestinationResolver $destinationResolver,
        private AvatarRenamePartResolver $partResolver,
        private AvatarRenameFilterValueResolver $filterValueResolver,
        private AvatarRenameNameParser $nameParser,
        private AvatarRenameGuardContextStore $guardContextStore,
        #[Autowire(service: 'state_machine.avatar_rename')]
        private WorkflowInterface $workflow,
    ) {
    }

    public function renameFromMessage(RenameAvatarMessage $message): void
    {
        $avatarTemp = $this->entityManager->find(AvatarTemp::class, $message->avatarTempId);

        if (!$avatarTemp instanceof AvatarTemp) {
            return;
        }

        if (AvatarRenameWorkflow::PLACE_RENAMED === $avatarTemp->getStatus()) {
            return;
        }

        if (AvatarRenameWorkflow::PLACE_RENAMING !== $avatarTemp->getStatus()) {
            throw new \RuntimeException(sprintf('Avatar rename message received in state "%s".', $avatarTemp->getStatus()));
        }

        $this->process($avatarTemp, $this->nameParser->fromAvatarTemp($avatarTemp));
    }

    private function process(AvatarTemp $avatarTemp, AvatarRenameInstruction $message): void
    {
        $this->assertSafeNewName($message->newName);
        $this->assertRequiredFilters($message);

        $destinationWebDir = $this->destinationResolver->resolveWebDirectory($message);
        $destinationDir = $this->destinationResolver->resolveAbsoluteDirectory($destinationWebDir);

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0775, true) && !is_dir($destinationDir)) {
            throw new \RuntimeException('Unable to create final avatar directory.');
        }

        $destinationPath = $destinationDir . '/' . $message->newName;
        $this->destinationResolver->assertDestinationPathIsAllowed($destinationPath);
        $imagePath = $destinationWebDir . '/' . $message->newName;

        $temporaryPath = $avatarTemp->getTempPath();
        if ((null === $temporaryPath || !is_file($temporaryPath)) && is_file($destinationPath)) {
            $this->completePartiallySuccessfulRename($avatarTemp, $message, $destinationPath, $imagePath);

            return;
        }

        $sourcePath = $this->sourcePathResolver->resolve($avatarTemp);

        $checksum = hash_file('sha256', $sourcePath);
        if (false === $checksum) {
            throw new \RuntimeException('Unable to calculate avatar checksum.');
        }

        $isExistingPart = false;
        if (file_exists($destinationPath)) {
            $avatarPart = $this->partResolver->resolveExistingPart($message, $imagePath);
            if (is_object($avatarPart)) {
                $isExistingPart = true;
                $this->hydrateReplacement($avatarPart, $message, $checksum, $imagePath);
            } else {
                $avatarPart = $this->resolveAvatarPart($message);
                $this->hydrateAvatarPart($avatarPart, $message, $checksum, $imagePath);
            }
        } else {
            $avatarPart = $this->resolveAvatarPart($message);
            $this->hydrateAvatarPart($avatarPart, $message, $checksum, $imagePath);
        }

        if (file_exists($destinationPath) && !unlink($destinationPath)) {
            throw new \RuntimeException('Unable to replace existing avatar file.');
        }

        if (!rename($sourcePath, $destinationPath)) {
            throw new \RuntimeException('Unable to rename avatar file.');
        }
        @rmdir(dirname($sourcePath));

        if (!$isExistingPart) {
            $this->entityManager->persist($avatarPart);
        }

        $this->applyMarkRenamed($avatarTemp, new AvatarRenameCompletionContext(
            $destinationPath,
            $message->newName,
            $checksum,
            $imagePath,
            $avatarPart,
        ));
        $this->entityManager->flush();

        // "renamed" is the verified terminal state which authorizes cleanup.
        $this->entityManager->remove($avatarTemp);
        $this->entityManager->flush();
    }

    private function completePartiallySuccessfulRename(
        AvatarTemp $avatarTemp,
        AvatarRenameInstruction $message,
        string $destinationPath,
        string $imagePath,
    ): void {
        $avatarPart = $this->partResolver->resolveExistingPart($message, $imagePath);
        $checksum = hash_file('sha256', $destinationPath);

        if (false === $checksum) {
            throw new \RuntimeException('Unable to resume a partially completed avatar rename.');
        }

        if (!is_object($avatarPart)) {
            $avatarPart = $this->resolveAvatarPart($message);
            $this->hydrateAvatarPart($avatarPart, $message, $checksum, $imagePath);
            $this->entityManager->persist($avatarPart);
        }

        $this->applyMarkRenamed($avatarTemp, new AvatarRenameCompletionContext(
            $destinationPath,
            $message->newName,
            $checksum,
            $imagePath,
            $avatarPart,
        ));
        $this->entityManager->flush();
        $this->entityManager->remove($avatarTemp);
        $this->entityManager->flush();
    }

    private function applyMarkRenamed(AvatarTemp $avatarTemp, AvatarRenameCompletionContext $context): void
    {
        $this->guardContextStore->setCompletion($avatarTemp, $context);

        try {
            $this->workflow->apply($avatarTemp, AvatarRenameWorkflow::TRANSITION_MARK_RENAMED, [
                'completion' => $context,
            ]);
        } finally {
            $this->guardContextStore->clearCompletion($avatarTemp);
        }
    }

    private function resolveAvatarPart(AvatarRenameInstruction $message): object
    {
        if ('body' !== $message->category) {
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
    private function resolveClothesForBody(AvatarRenameInstruction $message): array
    {
        $value = $message->filters['clothes'] ?? null;
        $slug = $this->resolveClothesSlug($value);

        if ('' === $slug || '-none-' === $slug) {
            return [];
        }

        $repository = $this->entityManager->getRepository(Clothes::class);
        if (!$repository instanceof ClothesRepository) {
            throw new \RuntimeException('Invalid clothes repository.');
        }

        $clothe = $repository->findOneByVariantSlug($slug);

        return $clothe instanceof Clothes ? [$clothe] : [];
    }

    private function resolveClothesSlug(mixed $value): string
    {
        $value = $this->extractFilterName($value);

        if ('' === $value) {
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

    private function assertRequiredFilters(AvatarRenameInstruction $message): void
    {
        foreach ($this->avatarRenameFilterMapper->getRequiredFilters($message->category) as $filterId) {
            if (!isset($message->filters[$filterId]) || '' === $this->extractFilterName($message->filters[$filterId])) {
                throw new \InvalidArgumentException(sprintf('Missing required avatar filter "%s".', $filterId));
            }
        }
    }

    private function hydrateAvatarPart(object $avatarPart, AvatarRenameInstruction $message, string $checksum, string $imagePath): void
    {
        $this->callRequiredSetter($avatarPart, 'setName', $this->partResolver->resolveName($message));
        $this->callRequiredSetter($avatarPart, 'setChecksum', $checksum);

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

    private function hydrateReplacement(
        object $avatarPart,
        AvatarRenameInstruction $message,
        string $checksum,
        string $imagePath,
    ): void {
        $this->callRequiredSetter($avatarPart, 'setChecksum', $checksum);

        if (method_exists($avatarPart, 'setImage')) {
            $avatarPart->setImage($imagePath);
        } elseif (method_exists($avatarPart, 'setImages')) {
            $avatarPart->setImages($this->partResolver->resolveImagesPayload(
                $avatarPart,
                $message,
                $imagePath,
                allowSideReplacement: true,
            ));
        }

        if (method_exists($avatarPart, 'setEditedAt')) {
            $avatarPart->setEditedAt(new \DateTimeImmutable());
        }

        $this->hydrateAvatarFilters($avatarPart, $message);

        if ('body' === $message->category && $avatarPart instanceof Body) {
            $clothes = $this->resolveClothesForBody($message);
            $avatarPart->setClothe($clothes[0] ?? null);
        }
    }

    private function hydrateAvatarFilters(object $avatarPart, AvatarRenameInstruction $message): void
    {
        foreach ($message->filters as $filterId => $filterValue) {
            $sourceClass = $this->avatarRenameFilterMapper->getFilterSourceClass($message->category, (string) $filterId);

            if (null === $sourceClass || null === $filterValue || '' === $this->extractFilterName($filterValue)) {
                continue;
            }

            if ('body' === $message->category && 'clothes' === (string) $filterId && $avatarPart instanceof Body) {
                continue;
            }

            if ('face' === $message->category && 'accessory' === (string) $filterId && '-none-' === $this->extractFilterName($filterValue)) {
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

        if ('body' === $message->category && $avatarPart instanceof Body) {
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
