<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Provider\AvatarSearchProvider;



final class SearchAvatarService
{
    public function __construct(
        // private readonly AvatarSearchProvider $avatarSearchProvider
        private readonly AvatarSearchProvider $avatarSearchProvider
    ) {}

    public function search(
        ?string $partie , 
        $filters = []

    ): array
    {
        // Pour l'instant, on utilise le provider existant
        // TODO: adapter pour utiliser tous les nouveaux paramètres
        return $this->avatarSearchProvider->searchAvatarPart(
            $partie,
            $filters
        );
    }
}
