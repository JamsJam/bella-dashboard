<?php

namespace App\Application\Avatar\Resolver;

use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use App\Entity\Clothes\Clothes;
use App\Message\Avatar\RenameAvatarMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AvatarRenameDestinationResolver
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AvatarRenameFilterMapper $avatarRenameFilterMapper,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function resolveWebDirectory(RenameAvatarMessage $message): string
    {
        $segments = [
            'images',
            'upload',
            'avatar',
            $this->normalizeToken($message->category),
        ];

        foreach ($this->avatarRenameFilterMapper->getStoragePathFilters($message->category) as $filterId) {
            $value = $message->filters[$filterId] ?? null;
            $filterName = $this->extractFilterName($value);

            if ($value === null || $filterName === '') {
                continue;
            }

            $folderName = $this->resolveFilterFolderName($message->category, $filterId, $filterName);
            if ($folderName !== '') {
                $segments[] = $folderName;
            }
        }

        return '/'.implode('/', $segments);
    }

    public function resolveAbsoluteDirectory(string $webDirectory): string
    {
        return $this->projectDir.'/public'.$webDirectory;
    }

    public function assertDestinationPathIsAllowed(string $path): void
    {
        $allowedRoot = $this->projectDir.'/public/images/upload/avatar';
        $allowedRoot = rtrim($allowedRoot, '/').'/';
        $directory = is_dir($path) ? $path : dirname($path);

        $realAllowedRoot = realpath($allowedRoot);
        $realDirectory = realpath($directory);

        if ($realAllowedRoot === false || $realDirectory === false || !str_starts_with($realDirectory.'/', rtrim($realAllowedRoot, '/').'/')) {
            throw new \RuntimeException('Path is outside the allowed avatar directory.');
        }
    }

    private function resolveFilterFolderName(string $part, string $filterId, string $value): string
    {
        $sourceClass = $this->avatarRenameFilterMapper->getFilterSourceClass($part, $filterId);
        if ($sourceClass === null) {
            return '';
        }

        if (ctype_digit($value)) {
            $entity = $this->entityManager->find($sourceClass, (int) $value);
            if (!is_object($entity)) {
                throw new \InvalidArgumentException('Unknown avatar filter id.');
            }

            if ($filterId === 'clothes' && $entity instanceof Clothes && $entity->getSlug() !== null) {
                return $this->normalizeToken($entity->getSlug());
            }

            return $this->normalizeToken($this->resolveEntityLabel($entity));
        }

        return $this->normalizeToken($value);
    }

    private function extractFilterName(mixed $value): string
    {
        if (is_array($value)) {
            $value = $value['name'] ?? '';
        }

        return trim((string) $value);
    }

    private function resolveEntityLabel(object $entity): string
    {
        if (method_exists($entity, 'getName') && is_string($entity->getName()) && $entity->getName() !== '') {
            return $entity->getName();
        }

        if (
            method_exists($entity, 'getSize')
            && is_object($entity->getSize())
            && method_exists($entity->getSize(), 'getName')
            && is_string($entity->getSize()->getName())
        ) {
            return $entity->getSize()->getName();
        }

        throw new \InvalidArgumentException(sprintf('Unable to resolve avatar filter label for "%s".', $entity::class));
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
