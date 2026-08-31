<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\BodyByAvatarCriteria;
use App\ApiResource\Avatar\BodyByClothesList;
use App\Repository\Avatar\Body\BodyRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<BodyByClothesList> */
final readonly class BodiesByClothesProvider implements ProviderInterface
{
    public function __construct(
        private BodyRepository $bodyRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): BodyByClothesList
    {
        $clothes = trim((string) $this->requestStack->getCurrentRequest()?->query->get('clothes', ''));
        if ('' === $clothes) {
            throw new BadRequestHttpException('Le paramètre clothes est obligatoire.');
        }

        $bodies = [];
        foreach ($this->bodyRepository->findByClothesSlug($clothes) as $body) {
            $id = $body->getId();
            $image = $body->getImage();
            if (null === $id || null === $image || '' === $image) {
                continue;
            }

            $bodies[] = new BodyByAvatarCriteria(
                id: $id,
                name: (string) $body->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new BodyByClothesList($clothes, $bodies);
    }

    private function absoluteUrl(string $path): string
    {
        if (false !== filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();

        return null === $request ? $path : $request->getSchemeAndHttpHost() . '/' . ltrim($path, '/');
    }
}
