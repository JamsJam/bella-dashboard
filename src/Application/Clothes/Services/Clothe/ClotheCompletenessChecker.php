<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Application\Clothes\Model\ClotheCompletenessResult;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;

final readonly class ClotheCompletenessChecker
{
    public function checkVariant(ClothesVariant $variant): ClotheCompletenessResult
    {
        $clothe = $variant->getClothes();
        $errors = [];
        if (!$clothe instanceof Clothes || '' === trim((string) $clothe->getName())) {
            $errors[] = 'Le nom du vêtement est obligatoire.';
        }
        if (!$clothe instanceof Clothes || ($clothe->getPrice() ?? 0) <= 0) {
            $errors[] = 'Le prix doit être supérieur à zéro.';
        }
        if (!$clothe instanceof Clothes || null === $clothe->getCollection()) {
            $errors[] = 'La collection est obligatoire.';
        }
        if (null === $variant->getColor()) {
            $errors[] = 'La couleur est obligatoire.';
        }
        if (null === $variant->getSize()) {
            $errors[] = 'La taille est obligatoire.';
        }
        if ([] === array_values(array_filter($variant->getImages() ?? []))) {
            $errors[] = 'Ajoutez au moins une image.';
        }
        if ('' === trim((string) $variant->getMetadescription())) {
            $errors[] = 'La meta description SEO est obligatoire.';
        }

        return new ClotheCompletenessResult($errors);
    }

    public function check(Clothes $clothe): ClotheCompletenessResult
    {
        $errors = [];

        if ('' === trim((string) $clothe->getName())) {
            $errors[] = 'Le nom du vêtement est obligatoire.';
        }
        if (($clothe->getPrice() ?? 0) <= 0) {
            $errors[] = 'Le prix doit être supérieur à zéro.';
        }
        if (null === $clothe->getCollection()) {
            $errors[] = 'La collection est obligatoire.';
        }
        if ($clothe->getVariants()->isEmpty()) {
            $errors[] = 'Au moins une variante est obligatoire.';
        }

        /** @var array<string, list<ClothesVariant>> $groups */
        $groups = [];
        foreach ($clothe->getVariants() as $variant) {
            if (!$variant instanceof ClothesVariant || null === $variant->getColor()) {
                $errors[] = 'Chaque variante doit avoir une couleur.';
                continue;
            }
            if (null === $variant->getSize()) {
                $errors[] = 'Chaque variante doit avoir une taille.';
            }
            $groups[(string) ($variant->getColor()->getId() ?? spl_object_id($variant->getColor()))][] = $variant;
        }

        foreach ($groups as $variants) {
            foreach ($variants as $variant) {
                $errors = [...$errors, ...$this->checkVariant($variant)->errors()];
            }
        }

        return new ClotheCompletenessResult(array_values(array_unique($errors)));
    }
}
