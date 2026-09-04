<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\MorphotypeBySkinColor;
use App\ApiResource\Avatar\MorphotypeBySkinColorList;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Skincolor;
use App\Repository\Avatar\Body\BodyRepository;
use App\Repository\Avatar\Body\MorphotypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<MorphotypeBySkinColorList> */
final readonly class MorphotypesBySkinColorAndMorphologyProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MorphotypeRepository $morphotypeRepository,
        private BodyRepository $bodyRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MorphotypeBySkinColorList
    {
        $skinColorId = $this->positiveId($uriVariables['id'] ?? null, 'Couleur de peau introuvable.');
        $morphologyId = $this->positiveId($uriVariables['morphologyId'] ?? null, 'Morphologie introuvable.');

        $skinColor = $this->entityManager->find(Skincolor::class, $skinColorId);
        if (!$skinColor instanceof Skincolor) {
            throw new NotFoundHttpException(sprintf('La couleur de peau %d est introuvable.', $skinColorId));
        }

        $morphology = $this->entityManager->find(Morphologie::class, $morphologyId);
        if (!$morphology instanceof Morphologie) {
            throw new NotFoundHttpException(sprintf('La morphologie %d est introuvable.', $morphologyId));
        }

        $morphotypes = [];
        foreach ($this->morphotypeRepository->findAvailableForSkinColorAndMorphologie($skinColor, $morphology) as $morphotype) {
            $id = $morphotype->getId();
            $size = $morphotype->getSize();
            $sizeId = $size?->getId();
            if (null === $id || null === $sizeId) {
                continue;
            }

            $image = $this->bodyRepository->findPreviewForMorphotype($skinColor, $morphotype)?->getImage();
            if (null === $image || '' === $image) {
                continue;
            }

            $morphotypes[] = new MorphotypeBySkinColor(
                id: $id,
                name: (string) $morphotype->getName(),
                sizeId: $sizeId,
                size: (string) $size->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new MorphotypeBySkinColorList($skinColorId, $morphologyId, $morphotypes);
    }

    private function positiveId(mixed $value, string $errorMessage): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if (false === $id || $id <= 0) {
            throw new NotFoundHttpException($errorMessage);
        }

        return $id;
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
