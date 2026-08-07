<?php

namespace App\Application\Clothes\Guard;

use App\Repository\Clothes\ClothesRepository;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class ClotheNameGuard
{
    public function __construct(
        private ClothesRepository $clothesRepository,
    ) {
    }

    public function assertNameAvailable(string $name, ?string $currentSlug = null): string
    {
        $name = $this->normalizeName($name);
        $slug = $this->createSlug($name);

        if (null !== $this->clothesRepository->findOneByNameOrSlugExcludingSlug($name, $slug, $currentSlug)) {
            throw new \InvalidArgumentException('Un autre vetement utilise deja ce nom.');
        }

        return $name;
    }

    public function createSlug(string $name): string
    {
        $slug = strtolower((string) (new AsciiSlugger())->slug($name));

        if ('' === $slug) {
            throw new \InvalidArgumentException('Le nom du vetement doit generer un slug valide.');
        }

        return $slug;
    }

    public function normalizeName(string $name): string
    {
        $name = trim($name);

        if ('' === $name) {
            throw new \InvalidArgumentException('Le nom du vetement est obligatoire.');
        }

        $name = preg_replace('/\s+/u', ' ', $name) ?? '';

        return $name;
    }
}
