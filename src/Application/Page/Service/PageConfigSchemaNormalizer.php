<?php

namespace App\Application\Page\Service;

final readonly class PageConfigSchemaNormalizer
{
    /**
     * Keeps the default structure while applying compatible saved values.
     *
     * @param array<string|int, mixed> $defaults
     * @param array<string|int, mixed> $saved
     *
     * @return array<string|int, mixed>
     */
    public function normalize(array $defaults, array $saved): array
    {
        $normalized = [];

        foreach ($defaults as $key => $defaultValue) {
            if (!array_key_exists($key, $saved)) {
                $normalized[$key] = $defaultValue;
                continue;
            }

            $normalized[$key] = $this->normalizeValue($defaultValue, $saved[$key]);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $defaultValue, mixed $savedValue): mixed
    {
        if ($this->isEmpty($savedValue)) {
            return $defaultValue;
        }

        if (is_array($defaultValue)) {
            if (!is_array($savedValue)) {
                return $defaultValue;
            }

            return $this->normalize($defaultValue, $savedValue);
        }

        if ($defaultValue === null) {
            return $savedValue;
        }

        if (get_debug_type($defaultValue) !== get_debug_type($savedValue)) {
            return $defaultValue;
        }

        return $savedValue;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null
            || $value === []
            || (is_string($value) && trim($value) === '');
    }
}
