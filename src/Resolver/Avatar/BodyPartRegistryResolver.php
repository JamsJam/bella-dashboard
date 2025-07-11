<?php

namespace App\Resolver\Avatar;

use App\Entity\Avatar\Body\Bodysize;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use App\Enum\Avatar\BodyPartEnum;
use App\Enum\Avatar\BodyContextEnum;
use App\Enum\Avatar\AvatarFilterEnum;
use App\Registry\Avatar\Entity\BodyEntityRegistry;
use App\Registry\Avatar\Entity\ColorEntityRegistry;
use App\Registry\Avatar\Entity\ShapeEntityRegistry;
use App\Registry\Avatar\Repository\BodyRepositoryRegistry;
use App\Registry\Avatar\Repository\ColorRepositoryRegistry;
use App\Registry\Avatar\Repository\ShapeRepositoryRegistry;

final class BodyPartRegistryResolver
{
    public function __construct(
        private BodyRepositoryRegistry $bodyRepositoryRegistry,
        private ColorRepositoryRegistry $colorRepositoryRegistry,
        private ShapeRepositoryRegistry $shapeRepositoryRegistry,
        private BodyEntityRegistry $bodyEntityRegistry,
        private ColorEntityRegistry $colorEntityRegistry,
        private ShapeEntityRegistry $shapeEntityRegistry,
    ) {
    }

    /** @throws \ValueError */
    public function getRepository(string $context, string $part): string
    {
        $bodyContext = BodyContextEnum::from($context);
        $bodyPart = BodyPartEnum::from($part);

        return match ($bodyContext) {
            BodyContextEnum::BODY   => $this->bodyRepositoryRegistry->getRepositoryClass($bodyPart),
            BodyContextEnum::COLOR  => $this->colorRepositoryRegistry->getRepositoryClass($bodyPart),
            BodyContextEnum::SHAPE  => $this->shapeRepositoryRegistry->getRepositoryClass($bodyPart),
            default => throw new \InvalidArgumentException("Invalid color entity type: $bodyContext->value"),
        };
    }

    /** @throws \ValueError */
    public function getEntity(string $context, string $part): string
    {
        $bodyContext = BodyContextEnum::from($context);
        $bodyPart = BodyPartEnum::from($part);

        return match ($bodyContext) {
            BodyContextEnum::BODY   => $this->bodyEntityRegistry->getEntityClass($bodyPart),
            BodyContextEnum::COLOR  => $this->colorEntityRegistry->getEntityClass($bodyPart),
            BodyContextEnum::SHAPE  => $this->shapeEntityRegistry->getEntityClass($bodyPart),
            default => throw new \InvalidArgumentException("Invalid color entity type: $bodyContext->value"),
        };
    }

    public function getFilters(?string $part): array
    {
        $filterType =  $this->getFilterType( $part) ;
        $bodyPart = BodyPartEnum::from($part);
        return match ($filterType) {
            AvatarFilterEnum::COLOR_AND_SHAPE_FILTER    => $this->getColorShapeFilterEntity($bodyPart) ,
            AvatarFilterEnum::SKIN_AND_SHAPE_FILTER     =>  $this->getSkinShapeFilterEntity($bodyPart),
            AvatarFilterEnum::BODY_FILTER               => $this->getBodyFilterEntity($bodyPart) ,
            default => throw new \InvalidArgumentException("Invalid color entity type: $filterType->value"),
        };
    }



    /**
     * Retourne le type de filtre pour cette partie du corps
     *
     * @param string|null $part
     * @return AvatarFilterEnum
     */
    private function getFilterType(?string $part): AvatarFilterEnum
    {
        $bodyPart = BodyPartEnum::from($part);
 
        return match ($bodyPart) {
            BodyPartEnum::HAIR,
            BodyPartEnum::EYE,
            BodyPartEnum::EYEBROWS,
            BodyPartEnum::MOUTH         => AvatarFilterEnum::COLOR_AND_SHAPE_FILTER,
            BodyPartEnum::NOSE,
            BodyPartEnum::FACE            => AvatarFilterEnum::SKIN_AND_SHAPE_FILTER,
            BodyPartEnum::BODY            => AvatarFilterEnum::BODY_FILTER,
            default => throw new \InvalidArgumentException("Invalid color entity type: $bodyPart->value"),
        };

    }




    private function getColorShapeFilterEntity(BodyPartEnum $bodypart): array
    {
        $colorFilterEntity = $this->getEntity('color',$bodypart->value);
        $shapeFilterEntity = $this->getEntity('shape',$bodypart->value);
        return [
            "colorFilter" => $colorFilterEntity,
            "shapeFilter" => $shapeFilterEntity
        ];
    }
    private function getSkinShapeFilterEntity(BodyPartEnum $bodypart): array
    {
        $skinColorFilterEntity = $this->getEntity('color','skin');
        $shapeFilterEntity = $this->getEntity('shape',$bodypart->value);
        return [
            "skincolorFilter" => $skinColorFilterEntity,
            "shapeFilter" => $shapeFilterEntity
            ];
    }
    private function getBodyFilterEntity():Array
    {
        $skinColorFilterEntity = $this->getEntity('color','skin');

        return [
            'skincolorFilter' => $skinColorFilterEntity,
            'morphotypeFilter' => Bodysize::class,
            'morphologieFilter' => Morphologie::class,
            'clothesFilter' => Clothes::class,
            'collectionFilter' => Collections::class,
        ];
    }

    
}
