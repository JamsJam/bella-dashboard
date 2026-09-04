<?php

namespace App\Application\Avatar\Services;

final readonly class AvatarPartSortService
{
    private const SORTS_BY_PART = [
        'body' => [
            'createdAt' => 'Date d’ajout',
            'skinColor' => 'Couleur de peau',
            'clothes' => 'Vêtement',
            'morphologie' => 'Morphologie',
            'morphotype' => 'Morphotype',
        ],
        'face' => [
            'createdAt' => 'Date d’ajout',
            'skinColor' => 'Couleur de peau',
            'shape' => 'Forme',
        ],
        'accessory' => [
            'createdAt' => 'Date d’ajout',
            'skinColor' => 'Couleur de peau',
            'shape' => 'Forme',
        ],
        'nose' => [
            'createdAt' => 'Date d’ajout',
            'skinColor' => 'Couleur de peau',
            'shape' => 'Forme',
        ],
        'eyebrows' => [
            'createdAt' => 'Date d’ajout',
            'color' => 'Couleur',
            'shape' => 'Forme',
        ],
        'eyes' => [
            'createdAt' => 'Date d’ajout',
            'color' => 'Couleur',
            'shape' => 'Forme',
        ],
        'hair' => [
            'createdAt' => 'Date d’ajout',
            'color' => 'Couleur',
            'shape' => 'Forme',
        ],
        'mouth' => [
            'createdAt' => 'Date d’ajout',
            'color' => 'Couleur',
            'shape' => 'Forme',
        ],
    ];

    /**
     * @return list<array{value: string, label: string}>
     */
    public function optionsFor(string $part): array
    {
        $options = [];

        foreach ($this->sortsFor($part) as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }

    /**
     * @param list<array<string, mixed>|object> $items
     *
     * @return list<array<string, mixed>|object>
     */
    public function sort(array $items, string $part, ?string $sort, ?string $direction): array
    {
        $availableSorts = $this->sortsFor($part);
        $sort = is_string($sort) && isset($availableSorts[$sort]) ? $sort : 'createdAt';
        $multiplier = 'asc' === strtolower((string) $direction) ? 1 : -1;

        usort($items, function (array|object $left, array|object $right) use ($part, $sort, $multiplier): int {
            $comparison = $this->compare(
                $this->value($left, $part, $sort),
                $this->value($right, $part, $sort),
            );

            if (0 === $comparison) {
                $comparison = $this->compare(
                    $this->rawValue($left, 'id'),
                    $this->rawValue($right, 'id'),
                );
            }

            return $comparison * $multiplier;
        });

        return array_values($items);
    }

    /**
     * @return array<string, string>
     */
    private function sortsFor(string $part): array
    {
        return self::SORTS_BY_PART[$part] ?? self::SORTS_BY_PART['body'];
    }

    private function value(array|object $item, string $part, string $sort): mixed
    {
        if ('createdAt' === $sort) {
            $date = $this->rawValue($item, 'createdAt');

            return $date instanceof \DateTimeInterface ? $date->getTimestamp() : strtotime((string) $date);
        }

        $segments = explode('__', (string) $this->rawValue($item, 'name'));
        $segmentIndex = match ($sort) {
            'color', 'skinColor' => 1,
            'shape', 'morphologie' => 2,
            'morphotype' => 3,
            'clothes' => 4,
            default => null,
        };

        return null === $segmentIndex ? '' : ($segments[$segmentIndex] ?? '');
    }

    private function rawValue(array|object $item, string $field): mixed
    {
        if (is_array($item)) {
            return $item[$field] ?? null;
        }

        $getter = 'get' . ucfirst($field);

        return method_exists($item, $getter) ? $item->{$getter}() : null;
    }

    private function compare(mixed $left, mixed $right): int
    {
        if (is_numeric($left) && is_numeric($right)) {
            return (float) $left <=> (float) $right;
        }

        return strnatcasecmp((string) $left, (string) $right);
    }
}
