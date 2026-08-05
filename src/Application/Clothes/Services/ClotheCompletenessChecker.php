<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Model\ClotheCompletenessResult;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;

final readonly class ClotheCompletenessChecker
{
    public function checkVariant(ClothesVariant $variant): ClotheCompletenessResult
    {
        $clothe = $variant->getClothes();
        $errors = [];
        if (!$clothe instanceof Clothes || trim((string) $clothe->getName()) === '') {
            $errors[] = 'Le nom du vêtement est obligatoire.';
        }
        if (!$clothe instanceof Clothes || ($clothe->getPrice() ?? 0) <= 0) {
            $errors[] = 'Le prix doit être supérieur à zéro.';
        }
        if (!$clothe instanceof Clothes || $clothe->getCollection() === null) {
            $errors[] = 'La collection est obligatoire.';
        }
        if ($variant->getColor() === null) {
            $errors[] = 'La couleur est obligatoire.';
        }
        if ($variant->getSize() === null) {
            $errors[] = 'La taille est obligatoire.';
        }
        if (array_values(array_filter($variant->getImages() ?? [])) === []) {
            $errors[] = 'Ajoutez au moins une image.';
        }

        return new ClotheCompletenessResult($errors);
    }

    public function check(Clothes $clothe): ClotheCompletenessResult
    {
        $errors = [];

        if (trim((string) $clothe->getName()) === '') {
            $errors[] = 'Le nom du vêtement est obligatoire.';
        }
        if (($clothe->getPrice() ?? 0) <= 0) {
            $errors[] = 'Le prix doit être supérieur à zéro.';
        }
        if ($clothe->getCollection() === null) {
            $errors[] = 'La collection est obligatoire.';
        }
        if ($clothe->getVariants()->isEmpty()) {
            $errors[] = 'Au moins une variante est obligatoire.';
        }

        /** @var array<string, list<ClothesVariant>> $groups */
        $groups = [];
        foreach ($clothe->getVariants() as $variant) {
            if (!$variant instanceof ClothesVariant || $variant->getColor() === null) {
                $errors[] = 'Chaque variante doit avoir une couleur.';
                continue;
            }
            if ($variant->getSize() === null) {
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
