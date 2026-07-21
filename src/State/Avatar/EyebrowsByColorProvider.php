<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\AvatarPartByColor;
use App\ApiResource\Avatar\AvatarPartByColorList;
use App\Entity\Avatar\Eyebrows\Eyebrowscolor;
use App\Repository\Avatar\Eyebrows\EyebrowsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AvatarPartByColorList> */
final readonly class EyebrowsByColorProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EyebrowsRepository $eyebrowsRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AvatarPartByColorList
    {
        $colorId = $this->colorId($uriVariables);
        $color = $this->entityManager->find(Eyebrowscolor::class, $colorId);

        if (!$color instanceof Eyebrowscolor) {
            throw new NotFoundHttpException(sprintf('La couleur des sourcils %d est introuvable.', $colorId));
        }

        $items = [];
        foreach ($this->eyebrowsRepository->findBy(['color' => $color], ['name' => 'ASC']) as $eyebrows) {
            $id = $eyebrows->getId();
            $image = $eyebrows->getImage();
            if ($id === null || $image === null || $image === '') {
                continue;
            }

            $items[] = new AvatarPartByColor($id, (string) $eyebrows->getName(), $this->absoluteUrl($image));
        }

        return new AvatarPartByColorList($colorId, 'eyebrows', $items);
    }

    private function colorId(array $uriVariables): int
    {
        $id = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id <= 0) {
            throw new NotFoundHttpException('Couleur des sourcils introuvable.');
        }

        return $id;
    }

    private function absoluteUrl(string $path): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL) !== false) {
            return $path;
        }

        $request = $this->requestStack->getCurrentRequest();

        return $request === null ? $path : $request->getSchemeAndHttpHost().'/'.ltrim($path, '/');
    }
}
