<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\AccessorizedFace;
use App\ApiResource\Avatar\AccessorizedFaceList;
use App\Application\Avatar\Services\FaceAccessoryNameMatcher;
use App\Entity\Avatar\Faces\Faces;
use App\Entity\Avatar\Skincolor;
use App\Repository\Avatar\Faces\FacesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AccessorizedFaceList> */
final readonly class AccessorizedFaceProvider implements ProviderInterface
{
    public function __construct(
        private FacesRepository $facesRepository,
        private FaceAccessoryNameMatcher $faceAccessoryNameMatcher,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AccessorizedFaceList
    {
        $skinColorId = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if (false === $skinColorId || $skinColorId <= 0) {
            throw new NotFoundHttpException('Couleur de peau introuvable.');
        }

        $skinColor = $this->entityManager->find(Skincolor::class, $skinColorId);
        if (!$skinColor instanceof Skincolor) {
            throw new NotFoundHttpException(sprintf('La couleur de peau %d est introuvable.', $skinColorId));
        }

        $items = [];

        foreach ($this->facesRepository->findBy(['skincolor' => $skinColor], ['name' => 'ASC']) as $face) {
            if (!$face instanceof Faces || !$this->faceAccessoryNameMatcher->matches((string) $face->getName())) {
                continue;
            }

            $id = $face->getId();
            $image = $face->getImage();
            if (null === $id || null === $image || '' === $image) {
                continue;
            }

            $items[] = new AccessorizedFace(
                id: $id,
                name: (string) $face->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new AccessorizedFaceList(
            skinColorId: $skinColorId,
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
