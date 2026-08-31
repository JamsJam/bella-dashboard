<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\AccessorizedFace;
use App\ApiResource\Avatar\AccessorizedFaceList;
use App\Entity\Avatar\Faces\Faces;
use App\Repository\Avatar\Faces\FacesRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AccessorizedFaceList> */
final readonly class AccessorizedFaceProvider implements ProviderInterface
{
    public function __construct(
        private FacesRepository $facesRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AccessorizedFaceList
    {
        $faceId = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if (false === $faceId || $faceId <= 0) {
            throw new NotFoundHttpException('Tête introuvable.');
        }

        $face = $this->facesRepository->find($faceId);
        if (!$face instanceof Faces) {
            throw new NotFoundHttpException(sprintf('La tête %d est introuvable.', $faceId));
        }

        $skinColorId = $face->getSkincolor()?->getId();
        $faceShapeId = $face->getShape()?->getId();
        if (null === $skinColorId || null === $faceShapeId) {
            throw new NotFoundHttpException(sprintf('La tête %d est incomplète.', $faceId));
        }

        $items = [];

        foreach ($this->facesRepository->findAccessorizedFor($face) as $accessorizedFace) {
            if (!$accessorizedFace instanceof Faces) {
                continue;
            }

            $id = $accessorizedFace->getId();
            $image = $accessorizedFace->getImage();
            if (null === $id || null === $image || '' === $image) {
                continue;
            }

            $items[] = new AccessorizedFace(
                id: $id,
                name: (string) $accessorizedFace->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new AccessorizedFaceList(
            faceId: $faceId,
            skinColorId: $skinColorId,
            faceShapeId: $faceShapeId,
            items: $items,
        );
    }

    private function absoluteUrl(string $path): string
    {
        if (false !== filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return $path;
        }

        return $request->getSchemeAndHttpHost() . '/' . ltrim($path, '/');
    }
}
