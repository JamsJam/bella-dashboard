<?php

namespace App\Application\Avatar\Provider;

use App\Application\Avatar\Services\AvatarPartSortService;
use App\Application\Avatar\Services\AvatarResolverService;
use App\Application\Avatar\Services\FaceAccessoryNameMatcher;
use Doctrine\ORM\EntityManagerInterface;

final class AvatarSearchProvider
{
    public function __construct(
        private readonly AvatarResolverService $resolverService,
        private readonly EntityManagerInterface $entityManager,
        private readonly FaceAccessoryNameMatcher $faceAccessoryNameMatcher,
        private readonly AvatarPartSortService $avatarPartSortService,
    ) {
    }

    /**
     * Recherche des avatars en fonction des critères fournis.
     *
     * @param string|null $partie  La partie de l'avatar à rechercher (body, hair, eyes, etc.)
     * @param array       $filters Tableau associatif des filtres à appliquer (search, color, shape, skinColor, morphologie, bodySize, morphotype, clothes, collection)
     *
     * @return array Tableau des avatars trouvés
     */
    public function searchAvatarPart(
        ?string $partie,
        array $filters = [],
    ): array|\InvalidArgumentException {
        $results = [];
        $partie = $partie ? strtolower($partie) : 'body';

        $entityClass = $this->getEntityClassForPart($partie);
        if ($entityClass) {
            $repository = $this->entityManager->getRepository($entityClass);

            $partResults = $this->searchInRepository(
                repository: $repository,
                filters: $filters
            );

            $partResults = match ($partie) {
                'face' => $this->filterFacesWithoutAccessories($partResults),
                'accessory' => $this->filterAccessories($partResults),
                default => $partResults,
            };

            $results[$partie] = $this->avatarPartSortService->sort(
                $partResults,
                $partie,
                isset($filters['sort']) ? (string) $filters['sort'] : null,
                isset($filters['direction']) ? (string) $filters['direction'] : null,
            );
        }

        return $results;
    }

    /**
     * Effectue une recherche dans un repository spécifique.
     *
     * @param object $repository Le repository dans lequel effectuer la recherche
     * @param array  $filters    Les filtres de recherche
     *
     * @return array Résultats de la recherche
     */
    private function searchInRepository(
        object $repository,
        array $filters,
    ): array|\BadFunctionCallException {
        // Utiliser la méthode findAllByFilters si elle existe dans le repository
        if (method_exists($repository, 'findPartByFilters')) {
            $skinColors = isset($filters['skinColor']) ? $filters['skinColor'] : null;
            $search = isset($filters['search']) ? $filters['search'] : null;
            $color = isset($filters['color']) ? $filters['color'] : null;
            $shape = isset($filters['shape']) ? $filters['shape'] : null;
            $morphologie = isset($filters['morphologie']) ? $filters['morphologie'] : null;
            $bodySize = isset($filters['bodySize']) ? $filters['bodySize'] : null;
            $morphotype = isset($filters['morphotype']) ? $filters['morphotype'] : null;
            $clothes = isset($filters['clothes']) ? $filters['clothes'] : null;
            $collection = isset($filters['collection']) ? $filters['collection'] : null;
            $accessory = isset($filters['accessory']) ? $filters['accessory'] : null;
            $sort = isset($filters['sort']) ? $filters['sort'] : null;
            $direction = isset($filters['direction']) ? $filters['direction'] : null;

            $filters = [
                'search' => $search,
                'color' => $color,
                'shape' => $shape,
                'skinColor' => $skinColors,
                'morphologie' => $morphologie,
                'bodySize' => $bodySize,
                'morphotype' => $morphotype,
                'clothes' => $clothes,
                'collection' => $collection,
                'accessory' => $accessory,
                'sort' => $sort,
                'direction' => $direction,
            ];

            $results = $repository->findPartByFilters(
                array_filter($filters),
            );

            return $this->filterBySearch($results, $search);
        }

        return new \BadFunctionCallException('Method not found');
    }

    private function filterBySearch(array $results, ?string $search): array
    {
        $search = trim((string) $search);

        if ('' === $search) {
            return $results;
        }

        return array_values(array_filter($results, static function (array|object $result) use ($search): bool {
            $name = is_array($result)
                ? (string) ($result['name'] ?? '')
                : (method_exists($result, 'getName') ? (string) $result->getName() : '');

            return false !== stripos($name, $search);
        }));
    }

    private function filterAccessories(array $results): array
    {
        return $this->filterFacesByName(
            $results,
            fn (string $name): bool =>
                $this->faceAccessoryNameMatcher->matches($name),
        );
    }

    private function filterFacesWithoutAccessories(array $results): array
    {
        return $this->filterFacesByName(
            $results,
            fn (string $name): bool =>
                $this->faceAccessoryNameMatcher
                    ->matchesWithoutAccessory($name),
        );
    }

    private function filterFacesByName(
        array $results,
        callable $matches,
    ): array {
        return array_values(array_filter(
            $results,
            static function (array|object $result) use ($matches): bool {
                $name = is_array($result)
                    ? (string) ($result['name'] ?? '')
                    : (
                        method_exists($result, 'getName')
                            ? (string) $result->getName()
                            : ''
                    );

                return $matches($name);
            },
        ));
    }

    /** Récupère le repository correspondant à une partie d'avatar donnée.
     *
     * @param string $part La partie de l'avatar (body, face, eyebrows, etc.)
     *
     * @return object Le repository correspondant à la partie d'avatar
     *
     * @throws \InvalidArgumentException Si la partie d'avatar n'est pas reconnue
     */
    private function getRepositoryClassForPart(string $part): string|\InvalidArgumentException
    {
        return $this->resolverService->resolveRepository($part);
    }

    /** Récupère l'entité correspondant à une partie d'avatar donnée.
     *
     * @param string $part La partie de l'avatar (body, face, eyebrows, etc.)
     *
     * @return string Le nom de l'entité correspondant à la partie d'avatar
     *
     * @throws \InvalidArgumentException Si la partie d'avatar n'est pas reconnue
     */
    private function getEntityClassForPart(string $part): string|\InvalidArgumentException
    {
        return $this->resolverService->resolveEntity($part);
    }
}
