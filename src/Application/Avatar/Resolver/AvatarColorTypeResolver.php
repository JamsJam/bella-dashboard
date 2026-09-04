<?php

namespace App\Application\Avatar\Resolver;

use App\Application\Avatar\Dto\AvatarColorTypeDefinitionDto;
use App\Entity\Avatar\Eyebrows\Eyebrowscolor;
use App\Entity\Avatar\Eyes\Eyecolor;
use App\Entity\Avatar\Hairs\Hairscolor;
use App\Entity\Avatar\Mouths\Mouthscolor;
use App\Entity\Avatar\Skincolor;

final readonly class AvatarColorTypeResolver
{
    private const TYPES = [
        'skin' => ['Peau', Skincolor::class, ['getNoses', 'getBodies', 'getFaces']],
        'hair' => ['Cheveux', Hairscolor::class, ['getHairs']],
        'eyes' => ['Yeux', Eyecolor::class, ['getEyes']],
        'eyebrows' => ['Sourcils', Eyebrowscolor::class, ['getEyebrows']],
        'mouth' => ['Bouche', Mouthscolor::class, ['getMouths']],
    ];

    public function resolve(string $type): AvatarColorTypeDefinitionDto
    {
        if (!isset(self::TYPES[$type])) {
            throw new \InvalidArgumentException(sprintf('Unknown avatar color type "%s".', $type));
        }

        [$label, $entityClass, $associationMethods] = self::TYPES[$type];

        return new AvatarColorTypeDefinitionDto($type, $label, $entityClass, $associationMethods);
    }

    /** @return list<AvatarColorTypeDefinitionDto> */
    public function all(): array
    {
        return array_map($this->resolve(...), array_keys(self::TYPES));
    }
}
