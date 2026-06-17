<?php

namespace App\Application\Avatar\Mapper;

final readonly class AvatarRenameFilterMapper
{
    private const REQUIRED_FILTERS_BY_PART = [
        'body' => ['skinColor', 'morphologie', 'bodySize'],
        'face' => ['shape'],
        'eyebrows' => ['color', 'shape'],
        'eyes' => ['color', 'shape'],
        'hair' => ['color', 'shape', 'side'],
        'mouth' => ['color', 'shape'],
        'nose' => ['skinColor', 'shape'],
    ];

    private const CREATABLE_FILTERS_BY_PART = [
        'body' => ['skinColor', 'morphologie', 'bodySize'],
        'face' => ['skinColor', 'shape'],
        'eyebrows' => ['color', 'shape'],
        'eyes' => ['color', 'shape'],
        'hair' => ['color', 'shape'],
        'mouth' => ['color', 'shape'],
        'nose' => ['skinColor', 'shape'],
    ];

    private const SETTERS_BY_FILTER = [
        'skinColor' => 'setSkincolor',
        'clothes' => 'setClothe',
    ];

    private const STORAGE_PATH_FILTERS = [
        'skinColor',
        'color',
        'shape',
        'morphologie',
        'bodySize',
        'clothes',
    ];

    public function __construct(
        private AvatarFilterMapper $avatarFilterMapper,
    ) {
    }

    public function getFilterSourceClass(string $part, string $filterId): ?string
    {
        return $this->avatarFilterMapper->getFilterSourceClass($part, $filterId);
    }

    /**
     * @return list<string>
     */
    public function getRequiredFilters(string $part): array
    {
        return self::REQUIRED_FILTERS_BY_PART[$part] ?? [];
    }

    public function isRequiredFilter(string $part, string $filterId): bool
    {
        return in_array($filterId, $this->getRequiredFilters($part), true);
    }

    public function isCreatableFilter(string $part, string $filterId): bool
    {
        return in_array($filterId, self::CREATABLE_FILTERS_BY_PART[$part] ?? [], true);
    }

    public function getSetterForFilter(string $filterId): string
    {
        return self::SETTERS_BY_FILTER[$filterId] ?? 'set'.ucfirst($filterId);
    }

    /**
     * @return list<string>
     */
    public function getStoragePathFilters(string $part): array
    {
        return array_values(array_filter(
            self::STORAGE_PATH_FILTERS,
            fn (string $filterId): bool => $this->getFilterSourceClass($part, $filterId) !== null,
        ));
    }
}
