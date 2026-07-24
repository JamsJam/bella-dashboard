<?php

namespace App\Application\Avatar\Resolver;



final class AvatarEntityResolver extends AbstractResolver
{
    public function map(): array
    {
            return [
                'body' => \App\Entity\Avatar\Body\Body::class,
                'eyebrows' => \App\Entity\Avatar\Eyebrows\Eyebrows::class,
                'eyes' => \App\Entity\Avatar\Eyes\Eye::class,
                'face' => \App\Entity\Avatar\Faces\Faces::class,
                'accessory' => \App\Entity\Avatar\Faces\Faces::class,
                'hair' => \App\Entity\Avatar\Hairs\Hairs::class,
                'mouth' => \App\Entity\Avatar\Mouths\Mouths::class,
                'nose' => \App\Entity\Avatar\Noses\Nose::class,
            ];
    }


}
