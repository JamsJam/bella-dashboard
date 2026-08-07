<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\AvatarPartByColor;
use App\ApiResource\Avatar\AvatarPartByColorList;
use App\Entity\Avatar\Mouths\Mouthscolor;
use App\Repository\Avatar\Mouths\MouthsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<AvatarPartByColorList> */
final readonly class MouthsByColorProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MouthsRepository $mouthsRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AvatarPartByColorList
    {
        $colorId = $this->colorId($uriVariables);
        $color = $this->entityManager->find(Mouthscolor::class, $colorId);

        if (!$color instanceof Mouthscolor) {
            throw new NotFoundHttpException(sprintf('La couleur de bouche %d est introuvable.', $colorId));
        }

        $items = [];
        foreach ($this->mouthsRepository->findBy(['color' => $color], ['name' => 'ASC']) as $mouth) {
            $id = $mouth->getId();
            $image = $mouth->getImage();
            if (null === $id || null === $image || '' === $image) {
                continue;
            }

            $items[] = new AvatarPartByColor($id, (string) $mouth->getName(), $this->absoluteUrl($image));
        }

        return new AvatarPartByColorList($colorId, 'mouths', $items);
    }

    private function colorId(array $uriVariables): int
    {
        $id = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if (false === $id || $id <= 0) {
            throw new NotFoundHttpException('Couleur de bouche introuvable.');
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
