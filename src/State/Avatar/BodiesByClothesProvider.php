<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\BodyByClothes;
use App\ApiResource\Avatar\BodyByClothesList;
use App\Entity\Clothes\ClothesVariant;
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
            $skinColor = $body->getSkincolor();
            $morphotype = $body->getMorphotype();
            $morphology = $morphotype?->getMorphologie();
            $size = $morphotype?->getSize();

            if (
                null === $id
                || null === $image
                || '' === $image
                || null === $skinColor?->getId()
                || null === $morphotype?->getId()
                || null === $morphology?->getId()
                || null === $size?->getId()
            ) {
                continue;
            }

            $clothesVariant = $this->clothesVariant($body->getClothesVariants()->toArray(), $clothes);
            $clothe = $clothesVariant?->getClothes();

            $bodies[] = new BodyByClothes(
                bodyId: $id,
                bodyName: (string) $body->getName(),
                image: $this->absoluteUrl($image),
                skinColorId: $skinColor->getId(),
                skinColor: (string) $skinColor->getName(),
                morphologyId: $morphology->getId(),
                morphology: (string) $morphology->getName(),
                morphotypeId: $morphotype->getId(),
                morphotype: (string) $morphotype->getName(),
                sizeId: $size->getId(),
                size: (string) $size->getName(),
                clothesId: $clothe?->getId(),
                clothesSlug: $clothesVariant?->getSlug(),
            );
        }

        return new BodyByClothesList($clothes, $bodies);
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function clothesVariant(array $variants, string $clothes): ?ClothesVariant
    {
        if ('none' === strtolower($clothes)) {
            return null;
        }

        foreach ($variants as $variant) {
            if ($variant->getSlug() === $clothes) {
                return $variant;
            }
        }

        return null;
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
