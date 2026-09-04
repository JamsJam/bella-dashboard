<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\MorphologyBySkinColor;
use App\ApiResource\Avatar\MorphologyBySkinColorList;
use App\Entity\Avatar\Skincolor;
use App\Repository\Avatar\Body\BodyRepository;
use App\Repository\Avatar\Body\MorphologieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<MorphologyBySkinColorList> */
final readonly class MorphologiesBySkinColorProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MorphologieRepository $morphologieRepository,
        private BodyRepository $bodyRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): MorphologyBySkinColorList
    {
        $skinColorId = $this->skinColorId($uriVariables);
        $skinColor = $this->entityManager->find(Skincolor::class, $skinColorId);

        if (!$skinColor instanceof Skincolor) {
            throw new NotFoundHttpException(sprintf('La couleur de peau %d est introuvable.', $skinColorId));
        }

        $morphologies = [];
        foreach ($this->morphologieRepository->findAvailableForSkinColor($skinColor) as $morphology) {
            $id = $morphology->getId();
            if (null === $id) {
                continue;
            }

            $image = $this->bodyRepository->findPreviewForMorphology($skinColor, $morphology)?->getImage();
            if (null === $image || '' === $image) {
                continue;
            }

            $morphologies[] = new MorphologyBySkinColor(
                id: $id,
                name: (string) $morphology->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new MorphologyBySkinColorList($skinColorId, $morphologies);
    }

    private function skinColorId(array $uriVariables): int
    {
        $id = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if (false === $id || $id <= 0) {
            throw new NotFoundHttpException('Couleur de peau introuvable.');
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
