<?php

namespace App\Application\Avatar\Services;

final readonly class FaceAccessoryNameMatcher
{
    public function matches(string $name): bool
    {
        $lastElement = $this->lastElement($name);

        return null !== $lastElement && '-none-' !== $lastElement;
    }

    public function matchesWithoutAccessory(string $name): bool
    {
        return '-none-' === $this->lastElement($name);
    }

    private function lastElement(string $name): ?string
    {
        $elements = explode('__', $name);

        if (count($elements) < 4) {
            throw new \InvalidArgumentException(sprintf(
                'Le nom de visage "%s" est incomplet.',
                $name,
            ));
        }

        $lastElement = end($elements);

        if (!is_string($lastElement) || '' === trim($lastElement)) {
            throw new \InvalidArgumentException(sprintf(
                'Le dernier segment du nom de visage "%s" est vide.',
                $name,
            ));
        }

        return $lastElement;
    }
}
