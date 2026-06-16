<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Guard\ClotheNameGuard;
use App\Entity\Clothes\Clothes;

final readonly class ClotheRenameService
{
    public function __construct(
        private ClotheNameGuard $clotheNameGuard,
    ) {
    }

    /**
     * @param list<Clothes> $variants
     */
    public function renameVariants(array $variants, string $currentSlug, string $newName): string
    {
        $name = $this->clotheNameGuard->assertNameAvailable($newName, $currentSlug);
        $slug = $this->clotheNameGuard->createSlug($name);
        $now = new \DateTimeImmutable();

        foreach ($variants as $variant) {
            if (!$variant instanceof Clothes) {
                continue;
            }

            $variant
                ->setName($name)
                ->setSlug($slug)
                ->setEditedAt($now);
        }

        return $slug;
    }
}
