<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\SkinColorAvatarPart;
use App\ApiResource\Avatar\SkinColorAvatarPartList;
use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Faces\Faces;
use App\Entity\Avatar\Noses\Nose;
use App\Entity\Avatar\Skincolor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<SkinColorAvatarPartList> */
final readonly class SkinColorAvatarPartProvider implements ProviderInterface
{
    private const PART_CLASSES = [
        'faces' => Faces::class,
        'noses' => Nose::class,
        'bodies' => Body::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SkinColorAvatarPartList
    {
        $skinColorId = filter_var($uriVariables['id'] ?? null, FILTER_VALIDATE_INT);
        if ($skinColorId === false || $skinColorId <= 0) {
            throw new NotFoundHttpException('Couleur de peau introuvable.');
        }

        $skinColor = $this->entityManager->find(Skincolor::class, $skinColorId);
        if (!$skinColor instanceof Skincolor) {
            throw new NotFoundHttpException(sprintf('La couleur de peau %d est introuvable.', $skinColorId));
        }

        $type = $operation->getExtraProperties()['avatarPart'] ?? null;
        if (!is_string($type) || !isset(self::PART_CLASSES[$type])) {
            throw new \LogicException('Unsupported skin color avatar part type.');
        }

        $criteria = ['skincolor' => $skinColor];
        $morphotypeId = null;

        if ($type === 'bodies' && array_key_exists('morphotypeId', $uriVariables)) {
            $morphotypeId = filter_var($uriVariables['morphotypeId'], FILTER_VALIDATE_INT);
            if ($morphotypeId === false || $morphotypeId <= 0) {
                throw new NotFoundHttpException('Morphotype introuvable.');
            }

            $morphotype = $this->entityManager->find(Morphotype::class, $morphotypeId);
            if (!$morphotype instanceof Morphotype) {
                throw new NotFoundHttpException(sprintf('Le morphotype %d est introuvable.', $morphotypeId));
            }

            $criteria['morphotype'] = $morphotype;
        }

        $items = [];
        foreach ($this->entityManager->getRepository(self::PART_CLASSES[$type])->findBy(
            $criteria,
            ['name' => 'ASC'],
        ) as $part) {
            if (
                !method_exists($part, 'getId')
                || !method_exists($part, 'getName')
                || !method_exists($part, 'getImage')
            ) {
                continue;
            }

            $id = $part->getId();
            $image = $part->getImage();
            if (!is_int($id) || !is_string($image) || $image === '') {
                continue;
            }

            $items[] = new SkinColorAvatarPart(
                id: $id,
                name: (string) $part->getName(),
                image: $this->absoluteUrl($image),
            );
        }

        return new SkinColorAvatarPartList(
            skinColorId: $skinColorId,
            type: $type,
            items: $items,
            morphotypeId: $morphotypeId,
        );
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
