<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\AccessorizedFace;
use App\ApiResource\Avatar\AccessorizedFaceList;
use App\Application\Avatar\Services\FaceAccessoryNameMatcher;
use App\Entity\Avatar\Faces\Faces;
use App\Repository\Avatar\Faces\FacesRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<AccessorizedFaceList> */
final readonly class AccessorizedFaceProvider implements ProviderInterface
{
    public function __construct(
        private FacesRepository $facesRepository,
        private FaceAccessoryNameMatcher $faceAccessoryNameMatcher,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AccessorizedFaceList
    {
        $items = [];

        foreach ($this->facesRepository->findBy([], ['name' => 'ASC']) as $face) {
            if (!$face instanceof Faces || !$this->faceAccessoryNameMatcher->matches((string) $face->getName())) {
                continue;
            }

            $id = $face->getId();
            $image = $face->getImage();
            if ($id === null || $image === null || $image === '') {
                continue;
            }

            $items[] = new AccessorizedFace(
                id: $id,
                name: (string) $face->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new AccessorizedFaceList($items);
    }

    private function absoluteUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return $path;
        }

        return $request->getSchemeAndHttpHost().'/'.ltrim($path, '/');
    }
}
