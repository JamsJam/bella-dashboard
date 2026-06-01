<?php

namespace App\Application\Avatar\Factory\ProductGrid;

use App\Application\Avatar\Provider\AvatarSearchProvider;
use App\UI\ProductGrid\ProductGridItemModel;


class AvatarProductGridItemsFactory
{
    public function __construct(
        private readonly AvatarSearchProvider $avatarSearchProvider,
    ){}

    /**
     * Crée les items du product grid à partir des parties d'avatar et des filtres sélectionnés
     *
     * @param string $part La partie d'avatar pour laquelle créer les items (ex: 'body', 'head', etc.)
     * @param array $filters Les filtres sélectionnés par l'utilisateur
     * @return ProductGridItemModel[] Liste des items du product grid à afficher
     */
    public function createAvatarProductItemssbyPart(string $part, array $filters = []): array
    {

        // Récupérer les parties d'avatar correspondant à la partie spécifiée et aux filtres sélectionnés
        // Construire et retourner les items du product grid à partir de ces parties d'avatar

        $searchResults = $this->avatarSearchProvider->searchAvatarPart(
            partie: $part,
            filters: $filters
        );
        // dump($searchResults,empty($searchResults[$part]));
        return !empty($searchResults[$part]) ? array_map([self::class, 'createProductGridItem'], $searchResults[$part]) : [];
        
    }

    /**
     * Crée un item du product grid à partir d'une partie d'avatar
     *
     * @param array $avatarPart Les données de la partie d'avatar
     * @return ProductGridItemModel L'item du product grid correspondant à la partie d'avatar
     */
    private static function createProductGridItem(array $avatarPart): ProductGridItemModel
    {
        
        return new ProductGridItemModel(
            id: (string) $avatarPart['id'],
            name: (string) $avatarPart['name'],
            imageUrl: self::resolveImageUrl($avatarPart),
            imageUrls: self::resolveImageUrls($avatarPart),
            
        );
    }

    private static function resolveImageUrl(array $avatarPart): string
    {
        if (!empty($avatarPart['imageUrl'])) {
            return (string) $avatarPart['imageUrl'];
        }

        if (!empty($avatarPart['image'])) {
            return (string) $avatarPart['image'];
        }

        if (!empty($avatarPart['images']) && is_array($avatarPart['images'])) {
            return (string) ($avatarPart['images'][0] ?? $avatarPart['images']['front'] ?? reset($avatarPart['images']) ?: '');
        }

        return '';
    }

    private static function resolveImageUrls(array $avatarPart): array
    {
        if (!empty($avatarPart['imageUrls']) && is_array($avatarPart['imageUrls'])) {
            return array_values(array_filter($avatarPart['imageUrls']));
        }

        if (!empty($avatarPart['images']) && is_array($avatarPart['images'])) {
            return array_values(array_filter($avatarPart['images']));
        }

        $imageUrl = self::resolveImageUrl($avatarPart);

        return $imageUrl !== '' ? [$imageUrl] : [];
    }

}
