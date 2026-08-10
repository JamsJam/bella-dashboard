<?php

namespace App\Application\Avatar\Mapper;

use App\Application\Avatar\Dto\AvatarPartViewDto;
use App\Entity\Avatar\Faces\Faces;

final readonly class AvatarPartViewMapper
{
    public function map(object $avatarPart): AvatarPartViewDto
    {
        return new AvatarPartViewDto(
            id: $this->resolveId($avatarPart),
            name: $this->resolveName($avatarPart),
            imageUrl: $this->resolveImageUrl($avatarPart),
            imageUrls: $this->resolveImageUrls($avatarPart),
            imageSides: $this->resolveImageSides($avatarPart),
            attributes: $this->resolveAttributes($avatarPart),
        );
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
