<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Body\Bodysize;
use App\Entity\Avatar\Body\Morphologie;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Eyebrows\Eyebrowscolor;
use App\Entity\Avatar\Eyebrows\Eyebrowshape;
use App\Entity\Avatar\Eyes\Eyecolor;
use App\Entity\Avatar\Eyes\Eyeshape;
use App\Entity\Avatar\Faces\FaceAccessory;
use App\Entity\Avatar\Faces\Faceshape;
use App\Entity\Avatar\Hairs\Hairscolor;
use App\Entity\Avatar\Hairs\Hairshape;
use App\Entity\Avatar\Mouths\Mouthscolor;
use App\Entity\Avatar\Mouths\Mouthshape;
use App\Entity\Avatar\Noses\Noseshape;
use App\Entity\Avatar\Skincolor;
use Doctrine\Persistence\ObjectManager;

final class AvatarFilterFixtures extends AbstractBaseFixtures
{
    public const SKIN_COLORS = ['light', 'medium', 'dark'];
    public const BODY_SIZES = ['S', 'M', 'L', 'XL'];
    public const MORPHOLOGIES = ['mince', 'standard', 'athletique', 'ronde', 'musclee'];
    public const EYEBROW_COLORS = ['brown', 'blond', 'black', 'red'];
    public const EYEBROW_SHAPES = ['straight', 'angled', 'thin', 'arched'];
    public const EYE_COLORS = ['blue', 'green', 'brown', 'grey'];
    public const EYE_SHAPES = ['round', 'almond', 'small', 'large'];
    public const FACE_SHAPES = ['oval', 'square', 'round'];
    public const FACE_ACCESSORIES = ['glasses', 'earrings', 'freckles'];
    public const HAIR_COLORS = ['black', 'brown', 'red', 'blond'];
    public const HAIR_SHAPES = ['short', 'long', 'curly', 'braids'];
    public const MOUTH_COLORS = ['pink', 'red', 'natural', 'plum'];
    public const MOUTH_SHAPES = ['smile', 'neutral', 'large', 'heart'];
    public const NOSE_SHAPES = ['small', 'straight', 'button', 'wide'];

    public function load(ObjectManager $manager): void
    {
        $skinColors = $this->createNamedEntities(
            $manager,
            Skincolor::class,
            self::SKIN_COLORS,
            FixtureReferences::SKIN_COLORS,
        );
        $sizes = $this->createNamedEntities($manager, Bodysize::class, self::BODY_SIZES, FixtureReferences::BODY_SIZES);
        $morphologies = $this->createNamedEntities(
            $manager,
            Morphologie::class,
            self::MORPHOLOGIES,
            FixtureReferences::MORPHOLOGIES,
        );

        foreach ($morphologies as $morphologyIndex => $morphology) {
            foreach ($sizes as $sizeIndex => $size) {
                $referenceIndex = $morphologyIndex * count($sizes) + $sizeIndex;
                $morphotype = (new Morphotype())
                    ->setName(sprintf('%s-%s', self::MORPHOLOGIES[$morphologyIndex], strtolower(self::BODY_SIZES[$sizeIndex])))
                    ->setSize($size)
                    ->setMorphologie($morphology);

                $this->persistTouched($manager, $morphotype);
                $this->addReference(FixtureReferences::MORPHOTYPES.$referenceIndex, $morphotype);
            }
        }

        $this->createNamedEntities(
            $manager,
            Eyebrowscolor::class,
            self::EYEBROW_COLORS,
            FixtureReferences::EYEBROWS_COLORS,
        );
        $this->createNamedEntities(
            $manager,
            Eyebrowshape::class,
            self::EYEBROW_SHAPES,
            FixtureReferences::EYEBROWS_SHAPES,
        );
        $this->createNamedEntities($manager, Eyecolor::class, self::EYE_COLORS, FixtureReferences::EYE_COLORS);
        $this->createNamedEntities($manager, Eyeshape::class, self::EYE_SHAPES, FixtureReferences::EYE_SHAPES);
        $this->createNamedEntities($manager, Faceshape::class, self::FACE_SHAPES, FixtureReferences::FACE_SHAPES);
        $this->createNamedEntities($manager, FaceAccessory::class, self::FACE_ACCESSORIES, FixtureReferences::FACE_ACCESSORIES);
        $this->createNamedEntities($manager, Hairscolor::class, self::HAIR_COLORS, FixtureReferences::HAIR_COLORS);
        $this->createNamedEntities($manager, Hairshape::class, self::HAIR_SHAPES, FixtureReferences::HAIR_SHAPES);
        $this->createNamedEntities($manager, Mouthscolor::class, self::MOUTH_COLORS, FixtureReferences::MOUTH_COLORS);
        $this->createNamedEntities($manager, Mouthshape::class, self::MOUTH_SHAPES, FixtureReferences::MOUTH_SHAPES);
        $this->createNamedEntities($manager, Noseshape::class, self::NOSE_SHAPES, FixtureReferences::NOSE_SHAPES);

        $manager->flush();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $className
     *
     * @return list<T>
     */
    private function createNamedEntities(
        ObjectManager $manager,
        string $className,
        array $names,
        string $referencePrefix,
    ): array {
        $entities = [];

        foreach ($names as $index => $name) {
            $entity = (new $className())->setName($name);
            $this->persistTouched($manager, $entity);
            $this->addReference($referencePrefix.$index, $entity);
            $entities[] = $entity;
        }

        return $entities;
    }
}
