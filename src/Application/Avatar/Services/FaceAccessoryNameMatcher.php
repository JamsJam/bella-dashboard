<?php

namespace App\Application\Avatar\Services;

final readonly class FaceAccessoryNameMatcher
{
    public function matches(string $name): bool
    {
        $parts = explode('__', $name);
        $accessory = $parts[3] ?? null;

        return is_string($accessory)
            && $accessory !== ''
            && $accessory !== '-none-';
    }
}
