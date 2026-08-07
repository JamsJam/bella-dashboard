<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\AvatarPartByColor;
use App\ApiResource\Avatar\AvatarPartByColorList;
use App\Entity\Avatar\Eyes\Eyecolor;
use App\Repository\Avatar\Eyes\EyeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AvatarPartByColorList> */
final readonly class EyesByColorProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EyeRepository $eyeRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AvatarPartByColorList
    {
        $colorId = $this->colorId($uriVariables);
        $color = $this->entityManager->find(Eyecolor::class, $colorId);

        if (!$color instanceof Eyecolor) {
            throw new NotFoundHttpException(sprintf('La couleur des yeux %d est introuvable.', $colorId));
        }

        $items = [];
        foreach ($this->eyeRepository->findBy(['color' => $color], ['name' => 'ASC']) as $eye) {
            $id = $eye->getId();
            $image = $eye->getImage();
            if (null === $id || null === $image || '' === $image) {
                continue;
            }

            $items[] = new AvatarPartByColor($id, (string) $eye->getName(), $this->absoluteUrl($image));
        }

        return new AvatarPartByColorList($colorId, 'eyes', $items);
    }

    private function colorId(array $uriVariables): int
    {
        $id = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if (false === $id || $id <= 0) {
            throw new NotFoundHttpException('Couleur des yeux introuvable.');
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
