<?php

namespace App\Application\Avatar\Resolver;

use App\Application\Avatar\Factory\AvatarPartFactory;
use App\Application\Avatar\Model\AvatarRenameInstruction;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AvatarRenamePartResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvatarPartFactory $avatarPartFactory,
    ) {
    }

    public function resolvePart(AvatarRenameInstruction $message): object
    {
        if ('hair' !== $message->category) {
            return $this->avatarPartFactory->createFromCategory($message->category);
        }

        $entityClass = $this->avatarPartFactory->resolveEntityClass($message->category);
        $existingHair = $this->entityManager->getRepository($entityClass)->findOneBy([
            'name' => $this->resolveName($message),
        ]);

        return is_object($existingHair) ? $existingHair : $this->avatarPartFactory->createFromCategory($message->category);
    }

    public function resolveExistingPart(AvatarRenameInstruction $message, string $imagePath): ?object
    {
        $entityClass = $this->avatarPartFactory->resolveEntityClass($message->category);
        $criteria = 'hair' === $message->category
            ? ['name' => $this->resolveName($message)]
            : ['image' => $imagePath];
        $avatarPart = $this->entityManager->getRepository($entityClass)->findOneBy($criteria);

        return is_object($avatarPart) ? $avatarPart : null;
    }

    public function resolveName(AvatarRenameInstruction $message): string
    {
        $name = pathinfo($message->newName, PATHINFO_FILENAME);

        if ('hair' === $message->category) {
            return preg_replace('/__(front|back)$/', '', $name) ?? $name;
        }

        return $name;
    }

    public function resolveImagesPayload(
        object $avatarPart,
        AvatarRenameInstruction $message,
        string $imagePath,
        bool $allowSideReplacement = false,
    ): array {
        if ('hair' !== $message->category) {
            return [$imagePath];
        }

        $side = $this->resolveHairSide($message);
        $images = method_exists($avatarPart, 'getImages') ? $avatarPart->getImages() : [];
        $images = is_array($images) ? $images : [];

        if (isset($images[$side]) && !$allowSideReplacement) {
            throw new \RuntimeException(sprintf('Hair "%s" image already exists.', $side));
        }

        $images[$side] = $imagePath;

        return $images;
    }

    private function resolveHairSide(AvatarRenameInstruction $message): string
    {
        $side = $this->normalizeToken((string) ($message->filters['side'] ?? ''));

        if (!in_array($side, ['front', 'back'], true)) {
            throw new \InvalidArgumentException('Invalid hair side.');
        }

        return $side;
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
