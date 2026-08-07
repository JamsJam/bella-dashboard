<?php

namespace App\Controller\Avatar;

use App\Application\Avatar\Services\AvatarResolverService;
use App\Entity\Avatar\Faces\Faces;
use App\Repository\Avatar\Faces\FacesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AvatarPartShowController extends AbstractController
{
    #[Route('/avatar/{part}/{id}', name: 'app_avatar_part_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(string $part, int $id, AvatarResolverService $avatarResolverService, EntityManagerInterface $entityManager): Response
    {
        $entityClass = $avatarResolverService->resolveEntity($part);
        $avatarPart = $entityManager->find($entityClass, $id);

        if (!is_object($avatarPart)) {
            throw $this->createNotFoundException('Avatar part not found.');
        }

        return $this->render('avatar/show.html.twig', [
            'breadscrumbs' => [
                ['label' => 'Avatar', 'route' => 'app_avatar'],
                ['label' => $this->resolveName($avatarPart)],
            ],
            'part' => $part,
            'avatar' => $this->mapAvatarPart($avatarPart),
            'similarAvatars' => array_map(
                fn (object $similarAvatar): array => $this->mapAvatarPart($similarAvatar),
                $this->findSimilarAvatars($entityManager, $entityClass, $avatarPart),
            ),
            'accessoryFaces' => array_map(
                fn (Faces $accessoryFace): array => $this->mapAvatarPart($accessoryFace),
                $this->findAccessoryFaces($entityManager, $avatarPart),
            ),
            'showAccessoryFacesSection' => $avatarPart instanceof Faces && null === $avatarPart->getAccessory(),
        ]);
    }

    /**
     * @return Faces[]
     */
    private function findAccessoryFaces(EntityManagerInterface $entityManager, object $avatarPart): array
    {
        if (!$avatarPart instanceof Faces || null !== $avatarPart->getAccessory()) {
            return [];
        }

        $repository = $entityManager->getRepository(Faces::class);

        return $repository instanceof FacesRepository ? $repository->findAccessorizedFor($avatarPart) : [];
    }

    private function findSimilarAvatars(EntityManagerInterface $entityManager, string $entityClass, object $avatarPart): array
    {
        $allParts = $entityManager->getRepository($entityClass)->findAll();

        return array_slice(array_values(array_filter(
            $allParts,
            fn (object $candidate): bool => $this->isSimilarAvatar($avatarPart, $candidate),
        )), 0, 12);
    }

    private function isSimilarAvatar(object $reference, object $candidate): bool
    {
        if ($this->resolveId($reference) === $this->resolveId($candidate)) {
            return false;
        }

        $hasComparison = false;

        foreach (['getShape', 'getColor', 'getSkincolor', 'getMorphotype', 'getAccessory'] as $getter) {
            if (!method_exists($reference, $getter) || !method_exists($candidate, $getter)) {
                continue;
            }

            $hasComparison = true;

            if ($this->resolveId($reference->{$getter}()) !== $this->resolveId($candidate->{$getter}())) {
                return false;
            }
        }

        return $hasComparison;
    }

    private function mapAvatarPart(object $avatarPart): array
    {
        return [
            'id' => $this->resolveId($avatarPart),
            'name' => $this->resolveName($avatarPart),
            'imageUrl' => $this->resolveImageUrl($avatarPart),
            'imageUrls' => $this->resolveImageUrls($avatarPart),
            'imageSides' => $this->resolveImageSides($avatarPart),
            'attributes' => $this->resolveAttributes($avatarPart),
        ];
    }

    private function resolveAttributes(object $avatarPart): array
    {
        $attributes = [];

        foreach (
            [
            'Couleur' => 'getColor',
            'Couleur de peau' => 'getSkincolor',
            'Forme' => 'getShape',
            'Morphotype' => 'getMorphotype',
            'Accessoire' => 'getAccessory',
            'Vetements' => 'getClothes',
            ] as $label => $getter
        ) {
            if (!method_exists($avatarPart, $getter)) {
                continue;
            }

            $value = $avatarPart->{$getter}();

            if ($value instanceof \Traversable) {
                $names = 'Vetements' === $label
                    ? $this->resolveDistinctSlugNames($value)
                    : $this->resolveTraversableNames($value);

                if ([] !== $names) {
                    $attributes[$label] = implode(', ', $names);
                }

                continue;
            }

            if (is_object($value)) {
                $attributes[$label] = $this->mapAttributeValue($value);
            }
        }

        if ($avatarPart instanceof Faces && null === $avatarPart->getAccessory()) {
            $attributes['Accessoire'] = '-none-';
        }

        return $attributes;
    }

    private function mapAttributeValue(object $value): string|array
    {
        $name = $this->resolveName($value);

        if (!method_exists($value, 'getHexa')) {
            return $name;
        }

        $hexa = strtoupper(ltrim((string) $value->getHexa(), '#'));

        return [
            'name' => $name,
            'hexa' => 1 === preg_match('/^[0-9A-F]{6}$/', $hexa) ? '#' . $hexa : null,
        ];
    }

    private function resolveTraversableNames(\Traversable $items): array
    {
        $names = [];

        foreach ($items as $item) {
            if (is_object($item)) {
                $names[] = $this->resolveName($item);
            }
        }

        return $names;
    }

    private function resolveDistinctSlugNames(\Traversable $items): array
    {
        $namesBySlug = [];

        foreach ($items as $item) {
            if (!is_object($item)) {
                continue;
            }

            $slug = method_exists($item, 'getSlug') ? (string) $item->getSlug() : '';
            $key = '' !== $slug ? $slug : (string) $this->resolveId($item);
            $namesBySlug[$key] ??= $this->resolveName($item);
        }

        return array_values($namesBySlug);
    }

    private function resolveImageUrl(object $avatarPart): string
    {
        if (method_exists($avatarPart, 'getImage') && $avatarPart->getImage()) {
            return (string) $avatarPart->getImage();
        }

        if (method_exists($avatarPart, 'getImages')) {
            $images = $avatarPart->getImages();

            return is_array($images) ? (string) ($images[0] ?? $images['front'] ?? reset($images) ?: '') : '';
        }

        return '';
    }

    private function resolveImageUrls(object $avatarPart): array
    {
        if (method_exists($avatarPart, 'getImages')) {
            $images = $avatarPart->getImages();

            return is_array($images) ? array_values(array_filter($images)) : [];
        }

        $imageUrl = $this->resolveImageUrl($avatarPart);

        return '' !== $imageUrl ? [$imageUrl] : [];
    }

    private function resolveImageSides(object $avatarPart): array
    {
        if (!method_exists($avatarPart, 'getImages')) {
            return [];
        }

        $images = $avatarPart->getImages();
        if (!is_array($images)) {
            return [];
        }

        return [
            'front' => (string) ($images['front'] ?? ''),
            'back' => (string) ($images['back'] ?? ''),
        ];
    }

    private function resolveName(?object $entity): string
    {
        if (null === $entity) {
            return '';
        }

        if (method_exists($entity, 'getName') && $entity->getName()) {
            return (string) $entity->getName();
        }

        if (method_exists($entity, 'getSize') && is_object($entity->getSize()) && method_exists($entity->getSize(), 'getName')) {
            return (string) $entity->getSize()->getName();
        }

        return '#' . (string) $this->resolveId($entity);
    }

    private function resolveId(?object $entity): ?int
    {
        return null !== $entity && method_exists($entity, 'getId') ? $entity->getId() : null;
    }
}
