<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Model\AvatarRenameInstruction;
use App\Entity\AvatarTemp;

final class AvatarRenameNameParser
{
    private const FILTERS_BY_CATEGORY = [
        'body' => ['skinColor', 'morphologie', 'bodySize', 'clothes'],
        'face' => ['skinColor', 'shape', 'accessory'],
        'eyebrows' => ['color', 'shape'],
        'eyes' => ['color', 'shape'],
        'hair' => ['color', 'shape', 'side'],
        'mouth' => ['color', 'shape'],
        'nose' => ['skinColor', 'shape'],
    ];

    public function fromAvatarTemp(AvatarTemp $avatarTemp): AvatarRenameInstruction
    {
        $newName = (string) $avatarTemp->getFinalName();
        $parts = explode('__', pathinfo($newName, PATHINFO_FILENAME));
        $categoryToken = array_shift($parts);
        $category = 'visage' === $categoryToken ? 'face' : (string) $categoryToken;

        if (!isset(self::FILTERS_BY_CATEGORY[$category])) {
            throw new \InvalidArgumentException('Unknown avatar category encoded in final filename.');
        }

        $filterNames = self::FILTERS_BY_CATEGORY[$category];
        if (count($parts) !== count($filterNames)) {
            throw new \InvalidArgumentException('The final avatar filename does not contain the expected filters.');
        }

        return new AvatarRenameInstruction(
            avatarTempId: (int) $avatarTemp->getId(),
            newName: $newName,
            category: $category,
            filters: array_combine($filterNames, $parts),
        );
    }
}
