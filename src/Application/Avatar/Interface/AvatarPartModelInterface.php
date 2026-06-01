<?php

namespace App\Application\Avatar\Interface;

interface AvatarPartModelInterface
{
    /**
     * Trouve les parties d'avatar selon les filtres fournis
     *
     * @param array $filters Tableau associatif des filtres à appliquer
     * @return array Liste des entités correspondant aux filtres
     */
    public function findPartByFilters(array $filters = []): array;

    /**
     * Récupère toutes les parties d'avatar disponibles
     *
     * @return array Liste de toutes les entités
     */
    public function findAllPart(): array;
}
