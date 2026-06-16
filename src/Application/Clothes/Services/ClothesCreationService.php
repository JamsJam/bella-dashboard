<?php

namespace App\Application\Clothes\Services;

use App\Application\Clothes\Persister\ClothesPersister;
use App\Entity\Collections\Collections;
use Symfony\Component\HttpFoundation\Request;

final class ClothesCreationService
{
    public function __construct(
        private readonly ClothesPersister $clothesPersister,
    ) {
    }

    public function createForCollectionFromRequest(Request $request, Collections $collection): void
    {
        $clothes = $request->request->all('clothes');
        if (!is_array($clothes) || $clothes === []) {
            return;
        }

        $enabledClothes = [];
        foreach ($clothes as $index => $data) {
            if (!is_array($data) || ($data['enabled'] ?? '0') !== '1') {
                continue;
            }

            $uploadedImages = $request->files->all('clotheImages_'.$index);
            $enabledClothes[] = [
                'data' => $data,
                'images' => is_array($uploadedImages) ? $uploadedImages : [],
            ];
        }

        if ($enabledClothes === []) {
            return;
        }

        $this->clothesPersister->createForCollection($collection, $enabledClothes);
    }
}
