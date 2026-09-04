<?php

namespace App\UI\Tabs;

use LogicException;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class TabsService
{
    public function __construct(
        private RequestStack $requestStack,
        private TabMapper $mapper,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function create(array $context = []): TabsDto
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new LogicException(
                'Impossible de créer les onglets sans requête courante.',
            );
        }

        return $this->mapper->map(
            controllerRoute: (string) $request->attributes->get('_route'),
            bestsellerOnly: $request->query->getBoolean('bestseller'),
            routeParameters: $request->attributes->all('_route_params'),
            context: $context,
        );
    }
}
