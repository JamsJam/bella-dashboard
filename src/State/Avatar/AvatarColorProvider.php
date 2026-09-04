<?php

namespace App\State\Avatar;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Avatar\AvatarColor;
use App\ApiResource\Avatar\AvatarColorList;
use App\Entity\Avatar\Eyebrows\Eyebrowscolor;
use App\Entity\Avatar\Eyes\Eyecolor;
use App\Entity\Avatar\Hairs\Hairscolor;
use App\Entity\Avatar\Mouths\Mouthscolor;
use Doctrine\ORM\EntityManagerInterface;

/** @implements ProviderInterface<AvatarColorList> */
final readonly class AvatarColorProvider implements ProviderInterface
{
    private const COLOR_CLASSES = [
        'mouth' => Mouthscolor::class,
        'hair' => Hairscolor::class,
        'eyes' => Eyecolor::class,
        'eyebrow' => Eyebrowscolor::class,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AvatarColorList
    {
        $type = $operation->getExtraProperties()['colorType'] ?? null;
        if (!is_string($type) || !isset(self::COLOR_CLASSES[$type])) {
            throw new \LogicException('Unsupported avatar color type.');
        }

        $colors = [];
        foreach ($this->entityManager->getRepository(self::COLOR_CLASSES[$type])->findBy([], ['name' => 'ASC']) as $color) {
            if (!method_exists($color, 'getId') || !method_exists($color, 'getName') || !method_exists($color, 'getHexa')) {
                continue;
            }

            $id = $color->getId();
            if (!is_int($id)) {
                continue;
            }

            $colors[] = new AvatarColor(
                id: $id,
                name: (string) $color->getName(),
                hexa: $color->getHexa(),
            );
        }

        return new AvatarColorList(
            type: $type,
            colors: $colors,
        );
    }
}
