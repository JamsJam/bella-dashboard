<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\HairByColor;
use App\ApiResource\Avatar\HairByColorList;
use App\Entity\Avatar\Hairs\Hairscolor;
use App\Repository\Avatar\Hairs\HairsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<HairByColorList> */
final readonly class HairByColorProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private HairsRepository $hairsRepository,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): HairByColorList
    {
        $hairColorId = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if (false === $hairColorId || $hairColorId <= 0) {
            throw new NotFoundHttpException('Couleur de cheveux introuvable.');
        }

        $hairColor = $this->entityManager->find(Hairscolor::class, $hairColorId);
        if (!$hairColor instanceof Hairscolor) {
            throw new NotFoundHttpException(sprintf('La couleur de cheveux %d est introuvable.', $hairColorId));
        }

        $hairs = [];
        foreach ($this->hairsRepository->findBy(['color' => $hairColor], ['name' => 'ASC']) as $hair) {
            $id = $hair->getId();
            if (null === $id) {
                continue;
            }

            $images = [];
            foreach ($hair->getImages() as $image) {
                if (is_string($image) && '' !== $image) {
                    $images[] = $this->absoluteUrl($image);
                }
            }

            $hairs[] = new HairByColor(
                id: $id,
                name: (string) $hair->getName(),
                images: $images,
            );
        }

        return new HairByColorList(
            hairColorId: $hairColorId,
            hairs: $hairs,
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
