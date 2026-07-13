<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
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
                new Tag('Auth', 'Authentification, inscription et verification de compte.'),
                new Tag('Pages', 'Contenus de pages exposes a l API.'),
                new Tag('Checkout', 'Panier et paiement.'),
                new Tag('Customers', 'Comptes clients.'),
                new Tag('Clothes', 'Vetements, collections et variants.'),
                new Tag('Avatar', 'Ressources avatar.'),
                new Tag('API', 'Autres routes API.'),
            ]);
    }

    private function tagPathItem(string $path, PathItem $pathItem): PathItem
    {
        $tag = $this->resolveTag($path);

        return $pathItem
            ->withGet($this->tagOperation($pathItem->getGet(), $tag))
            ->withPut($this->tagOperation($pathItem->getPut(), $tag))
            ->withPost($this->tagOperation($pathItem->getPost(), $tag))
            ->withDelete($this->tagOperation($pathItem->getDelete(), $tag))
            ->withPatch($this->tagOperation($pathItem->getPatch(), $tag));
    }

    private function tagOperation(?Operation $operation, string $tag): ?Operation
    {
        return $operation?->withTags([$tag]);
    }

    private function resolveTag(string $path): string
    {
        return match (true) {
            str_starts_with($path, '/api/auth') => 'Auth',
            str_starts_with($path, '/api/page') => 'Pages',
            str_starts_with($path, '/api/checkout') => 'Checkout',
            str_starts_with($path, '/api/customers') => 'Customers',
            str_contains($path, '/clothes') || str_contains($path, '/collections') || str_contains($path, '/categories') => 'Clothes',
            str_contains($path, '/avatar') || str_contains($path, '/morphotype') => 'Avatar',
            default => 'API',
        };
    }
}
