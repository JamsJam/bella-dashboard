<?php

namespace App\Application\Avatar\Resolver;

final class AvatarRepositoryResolver extends AbstractResolver
{
    public function map(): array
    {
        return [
            'body' => \App\Repository\Avatar\Body\BodyRepository::class,
            'eyebrows' => \App\Repository\Avatar\Eyebrows\EyebrowsRepository::class,
            'eyes' => \App\Repository\Avatar\Eyes\EyeRepository::class,
            'face' => \App\Repository\Avatar\Faces\FacesRepository::class,
            'accessory' => \App\Repository\Avatar\Faces\FacesRepository::class,
            'hair' => \App\Repository\Avatar\Hairs\HairsRepository::class,
            'mouth' => \App\Repository\Avatar\Mouths\MouthsRepository::class,
            'nose' => \App\Repository\Avatar\Noses\NoseRepository::class,
        ];
    }
}
