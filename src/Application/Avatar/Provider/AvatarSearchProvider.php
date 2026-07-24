<?php

namespace App\Application\Avatar\Provider;

use App\Application\Avatar\Services\AvatarResolverService;
use App\Application\Avatar\Services\FaceAccessoryNameMatcher;
use BadFunctionCallException;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class AvatarSearchProvider
{
    public function __construct(
        private readonly AvatarResolverService $resolverService,
        private readonly EntityManagerInterface $entityManager,
        private readonly FaceAccessoryNameMatcher $faceAccessoryNameMatcher,
    ) {
    }

    /**
     * Recherche des avatars en fonction des critères fournis
     *
     * @param string|null $partie La partie de l'avatar à rechercher (body, hair, eyes, etc.)
     * @param array $filters Tableau associatif des filtres à appliquer (search, color, shape, skinColor, morphologie, morphotype, clothes, collection)
     * @return array Tableau des avatars trouvés
     */
    public function searchAvatarPart(
        ?string $partie,
        array $filters = []
    ): array | InvalidArgumentException {
        $results = [];
        $partie = $partie ? strtolower($partie) : 'body';
        
        $entityClass  = $this->getEntityClassForPart($partie);
        if ($entityClass) {
            $repository = $this->entityManager->getRepository($entityClass);
            
            $partResults = $this->searchInRepository(
                repository: $repository, 
                filters: $filters
                );

            $results[$partie] = $partie === 'accessory'
                ? $this->filterAccessories($partResults)
                : $partResults;
        }

        return $results;
    }

    /**
     * Effectue une recherche dans un repository spécifique
     *
     * @param object $repository Le repository dans lequel effectuer la recherche
     * @param array $filters Les filtres de recherche
     * @return array Résultats de la recherche
     */
    private function searchInRepository(
        object $repository,
        array $filters
    ): array | BadFunctionCallException {
        // Utiliser la méthode findAllByFilters si elle existe dans le repository
        if (method_exists($repository, 'findPartByFilters')) {
            $skinColors = isset($filters['skinColor']) ? $filters['skinColor'] : null;
            $search = isset($filters['search']) ? $filters['search'] : null;
            $color = isset($filters['color']) ? $filters['color'] : null;
            $shape = isset($filters['shape']) ? $filters['shape'] : null;
            $morphologie = isset($filters['morphologie']) ? $filters['morphologie'] : null;
            $morphotype = isset($filters['morphotype']) ? $filters['morphotype'] : null;
            $clothes = isset($filters['clothes']) ? $filters['clothes'] : null;
            $collection = isset($filters['collection']) ? $filters['collection'] : null;
            $accessory = isset($filters['accessory']) ? $filters['accessory'] : null;


            $filters = [
                'search' => $search,
                'color' => $color,
                'shape' => $shape,
                'skinColor' => $skinColors,
                'morphologie' => $morphologie,
                'morphotype' => $morphotype,
                'clothes' => $clothes,
                'collection' => $collection,
                'accessory' => $accessory,
            ];

            
            $results = $repository->findPartByFilters(
                array_filter($filters),

            );

            return $this->filterBySearch($results, $search);

        }else{
            return new BadFunctionCallException('Method not found');
        }

    }

    private function filterBySearch(array $results, ?string $search): array
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $results;
        }

        return array_values(array_filter($results, static function (array|object $result) use ($search): bool {
            $name = is_array($result)
                ? (string) ($result['name'] ?? '')
                : (method_exists($result, 'getName') ? (string) $result->getName() : '');

            return stripos($name, $search) !== false;
        }));
    }

    private function filterAccessories(array $results): array
    {
        return array_values(array_filter(
            $results,
            function (array|object $result): bool {
                $name = is_array($result)
                    ? (string) ($result['name'] ?? '')
                    : (method_exists($result, 'getName') ? (string) $result->getName() : '');

                return $this->faceAccessoryNameMatcher->matches($name);
            },
        ));
    }






    /** Récupère le repository correspondant à une partie d'avatar donnée
     *
     * @param string $part La partie de l'avatar (body, face, eyebrows, etc.)
     * @return object Le repository correspondant à la partie d'avatar
     * @throws InvalidArgumentException Si la partie d'avatar n'est pas reconnue
     */
    private function getRepositoryClassForPart(string $part): string | InvalidArgumentException
    {
            return $this->resolverService->resolveRepository($part);
        
    }

    /** Récupère l'entité correspondant à une partie d'avatar donnée
     *
     * @param string $part La partie de l'avatar (body, face, eyebrows, etc.)
     * @return string Le nom de l'entité correspondant à la partie d'avatar
     * @throws InvalidArgumentException Si la partie d'avatar n'est pas reconnue
     */
    private function getEntityClassForPart(string $part): string | InvalidArgumentException
    {
            return $this->resolverService->resolveEntity($part);
        
    }
}
