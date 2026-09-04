<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Mapper\AvatarRenameFilterMapper;
use App\Application\Avatar\Resolver\AvatarRenameFilterValueResolver;

final readonly class AvatarValidatedFilterValueService
{
    private const PERSISTED_FILTERS = [
        'color',
        'skinColor',
        'shape',
        'accessory',
        'morphologie',
        'bodySize',
    ];

    public function __construct(
        private AvatarRenameFilterMapper $filterMapper,
        private AvatarRenameFilterValueResolver $filterValueResolver,
    ) {
    }

    /** @param array<string, mixed> $filters */
    public function persistNewValues(string $category, array $filters): void
    {
        foreach ($filters as $filterId => $value) {
            $filterId = (string) $filterId;
            if (!$this->shouldPersist($category, $filterId, $value)) {
                continue;
            }

            $sourceClass = $this->filterMapper->getFilterSourceClass($category, $filterId);
            if (null === $sourceClass) {
                continue;
            }

            $this->filterValueResolver->resolve(
                sourceClass: $sourceClass,
                part: $category,
                filterId: $filterId,
                value: $value,
                filters: $filters,
            );
        }
    }

    private function shouldPersist(string $category, string $filterId, mixed $value): bool
    {
        if (
            !in_array($filterId, self::PERSISTED_FILTERS, true)
            || !$this->filterMapper->isCreatableFilter($category, $filterId)
        ) {
            return false;
        }

        $name = is_array($value) ? ($value['name'] ?? null) : $value;
        if (!is_scalar($name)) {
            return false;
        }

        $name = trim((string) $name);

        // Existing select options carry their numeric Doctrine identifier.
        return '' !== $name
            && '-none-' !== $name
            && ('morphologie' === $filterId || !ctype_digit($name));
    }
}
