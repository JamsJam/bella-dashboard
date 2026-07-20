<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;

final readonly class ThematicOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $paths = new Paths();

        foreach ($openApi->getPaths()->getPaths() as $path => $pathItem) {
            $paths->addPath($path, $this->tagPathItem($path, $pathItem));
        }

        return $openApi
            ->withPaths($paths)
            ->withTags([
                new Tag('Auth', 'Authentification, inscription et vérification des comptes.'),
                new Tag('Pages', 'Contenus des pages exposés à l’API.'),
                new Tag('Checkout', 'Panier et paiement.'),
                new Tag('Customers', 'Comptes clients.'),
                new Tag('Clothes', 'Vêtements, catégories, collections et variantes.'),
                new Tag('Avatar', 'Éléments utilisés pour composer les avatars.'),
                new Tag('API', 'Autres routes API.'),
            ]);
    }

    private function tagPathItem(string $path, PathItem $pathItem): PathItem
    {
        $tag = $this->resolveTag($path);

        return $pathItem
            ->withGet($this->describeOperation($pathItem->getGet(), $tag, 'GET', $path))
            ->withPut($this->describeOperation($pathItem->getPut(), $tag, 'PUT', $path))
            ->withPost($this->describeOperation($pathItem->getPost(), $tag, 'POST', $path))
            ->withDelete($this->describeOperation($pathItem->getDelete(), $tag, 'DELETE', $path))
            ->withPatch($this->describeOperation($pathItem->getPatch(), $tag, 'PATCH', $path));
    }

    private function describeOperation(?Operation $operation, string $tag, string $method, string $path): ?Operation
    {
        if ($operation === null) {
            return null;
        }

        [$summary, $description, $successDescription] = $this->operationDescriptions($method, $path);
        $responses = $operation->getResponses() ?? [];
        $parameters = $operation->getParameters() ?? [];

        foreach ($responses as $status => $response) {
            $responseDescription = str_starts_with((string) $status, '2')
                ? $successDescription
                : $this->responseDescription((string) $status);

            if ($response instanceof Response) {
                $responses[$status] = $response->withDescription($responseDescription);
                continue;
            }

            if (is_array($response)) {
                $responses[$status]['description'] = $responseDescription;
                continue;
            }

            if ($response instanceof \ArrayObject) {
                $response['description'] = $responseDescription;
            }
        }

        foreach ($parameters as $index => $parameter) {
            if (!$parameter instanceof Parameter || $parameter->getIn() !== 'path') {
                continue;
            }

            $parameters[$index] = $parameter->withDescription(match ($parameter->getName()) {
                'category' => 'Slug de la catégorie dans laquelle rechercher les variantes.',
                'page' => 'Nom de la page dont le contenu doit être chargé.',
                'slug' => 'Slug de la déclinaison du vêtement à afficher.',
                'id' => sprintf('Identifiant de la ressource %s.', mb_strtolower($tag)),
                default => 'Identifiant utilisé pour sélectionner la ressource.',
            });
        }

        return $operation
            ->withTags([$tag])
            ->withSummary($summary)
            ->withDescription($description)
            ->withParameters($parameters)
            ->withResponses($responses);
    }

    private function responseDescription(string $status): string
    {
        return match ($status) {
            '400' => 'La requête envoyée est invalide.',
            '401' => 'Une authentification est nécessaire pour accéder à cette ressource.',
            '403' => 'Vous n’avez pas l’autorisation d’effectuer cette opération.',
            '404' => 'La ressource demandée est introuvable.',
            '409' => 'La requête entre en conflit avec l’état actuel de la ressource.',
            '422' => 'Les données envoyées ne peuvent pas être traitées.',
            '500' => 'Une erreur interne est survenue.',
            default => 'La requête n’a pas pu être traitée.',
        };
    }

    /**
     * @return array{string, string, string}
     */
    private function operationDescriptions(string $method, string $path): array
    {
        return match ($method.' '.$path) {
            'GET /api/category/{category}' => [
                'Lister les variantes d’une catégorie',
                'Retourne les variantes disponibles pour la catégorie identifiée par son slug.',
                'La liste des variantes de la catégorie a été retournée.',
            ],
            'GET /api/search/{category}' => [
                'Rechercher des variantes dans une catégorie',
                'Retourne les données nécessaires aux cartes des variantes, filtrées par couleur, taille et plage de prix.',
                'Les variantes correspondant aux filtres ont été retournées.',
            ],
            'GET /api/variant/{slug}' => [
                'Consulter le détail d’une déclinaison',
                'Regroupe les données du produit par slug avec ses tailles, son guide des tailles, ses autres couleurs et les produits de la même collection.',
                'Le détail de la déclinaison a été retourné.',
            ],
            'GET /api/variants/{id}/stock' => [
                'Consulter le stock d’une déclinaison',
                'Retourne le stock actuel et la disponibilité de la déclinaison identifiée.',
                'Le stock de la déclinaison a été retourné.',
            ],
            'POST /api/auth/login' => [
                'Se connecter',
                'Vérifie les identifiants du client et génère son jeton d’authentification.',
                'Le client est authentifié et son jeton a été généré.',
            ],
            'POST /api/auth/signup' => [
                'Créer un compte client',
                'Enregistre un nouveau client et démarre la procédure de confirmation du compte.',
                'Le compte client a été créé.',
            ],
            'POST /api/auth/verify-confirmation-code' => [
                'Confirmer un compte client',
                'Vérifie le code de confirmation reçu par le client et active son compte.',
                'Le compte client a été confirmé.',
            ],
            'GET /api/auth/verify-login' => [
                'Vérifier la connexion',
                'Indique si le jeton transmis correspond à un client actuellement authentifié.',
                'L’état de la connexion a été retourné.',
            ],
            'GET /api/morphotypes' => [
                'Lister les morphotypes',
                'Retourne tous les morphotypes disponibles pour la création d’un avatar.',
                'La liste des morphotypes a été retournée.',
            ],
            'POST /api/morphotypes' => [
                'Créer un morphotype',
                'Ajoute un nouveau morphotype utilisable par les avatars.',
                'Le morphotype a été créé.',
            ],
            'GET /api/morphotypes/{id}' => [
                'Consulter un morphotype',
                'Retourne le morphotype correspondant à l’identifiant demandé.',
                'Le morphotype a été retourné.',
            ],
            'DELETE /api/morphotypes/{id}' => [
                'Supprimer un morphotype',
                'Supprime définitivement le morphotype correspondant à l’identifiant demandé.',
                'Le morphotype a été supprimé.',
            ],
            'PATCH /api/morphotypes/{id}' => [
                'Modifier un morphotype',
                'Met à jour les propriétés transmises pour le morphotype demandé.',
                'Le morphotype a été mis à jour.',
            ],
            'POST /api/checkout/carts' => [
                'Créer une session de paiement',
                'Valide le panier et prépare la session nécessaire à son paiement.',
                'La session de paiement a été créée.',
            ],
            'GET /api/checkout/contry' => [
                'Lister les destinations de livraison',
                'Retourne les destinations configurées avec leur prix en centimes et leur drapeau.',
                'La liste des destinations de livraison a été retournée.',
            ],
            'GET /api/checkout/vat' => [
                'Consulter la TVA',
                'Retourne le taux de TVA configuré pour les commandes.',
                'Le taux de TVA a été retourné.',
            ],
            'GET /api/clothes' => [
                'Lister les vêtements',
                'Retourne la liste des vêtements disponibles dans le catalogue.',
                'La liste des vêtements a été retournée.',
            ],
            'POST /api/clothes' => [
                'Créer un vêtement',
                'Ajoute un nouveau vêtement au catalogue.',
                'Le vêtement a été créé.',
            ],
            'GET /api/clothes/{id}' => [
                'Consulter un vêtement',
                'Retourne le vêtement correspondant à l’identifiant demandé.',
                'Le vêtement a été retourné.',
            ],
            'DELETE /api/clothes/{id}' => [
                'Supprimer un vêtement',
                'Supprime définitivement le vêtement correspondant à l’identifiant demandé.',
                'Le vêtement a été supprimé.',
            ],
            'PATCH /api/clothes/{id}' => [
                'Modifier un vêtement',
                'Met à jour les propriétés transmises pour le vêtement demandé.',
                'Le vêtement a été mis à jour.',
            ],
            'POST /api/customers' => [
                'Créer un client',
                'Ajoute un nouveau compte client.',
                'Le client a été créé.',
            ],
            'GET /api/customers/{id}' => [
                'Consulter un client',
                'Retourne les informations du client correspondant à l’identifiant demandé.',
                'Les informations du client ont été retournées.',
            ],
            'PUT /api/customers/{id}' => [
                'Mettre à jour un client',
                'Remplace les informations du client correspondant à l’identifiant demandé.',
                'Les informations du client ont été mises à jour.',
            ],
            'GET /api/page/{page}' => [
                'Charger le contenu d’une page',
                'Retourne le contenu configuré pour la page identifiée par son nom.',
                'Le contenu de la page a été retourné.',
            ],
            default => [
                'Exécuter l’opération',
                'Exécute l’opération demandée sur la ressource.',
                'L’opération a été réalisée avec succès.',
            ],
        };
    }

    private function resolveTag(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/api/auth') => 'Auth',
            str_starts_with($path, '/api/page') => 'Pages',
            str_starts_with($path, '/api/checkout') => 'Checkout',
            str_starts_with($path, '/api/customers') => 'Customers',
            str_contains($path, '/clothes') || str_contains($path, '/collections') || str_contains($path, '/category') || str_contains($path, '/search') || str_contains($path, '/variant') => 'Clothes',
            str_contains($path, '/avatar') || str_contains($path, '/morphotype') => 'Avatar',
            default => 'API',
        };
    }
}
