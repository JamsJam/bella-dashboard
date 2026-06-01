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
    public const BODY_SIZES = ['S', 'M', 'L'];
    public const MORPHOLOGIES = ['mince', 'standard', 'athletique'];
    public const MORPHOTYPES = ['s', 'm', 'l'];
    public const COLORS = ['blue', 'green', 'brown', 'black', 'red'];
    public const SHAPES = ['round', 'angled', 'smile', 'button', 'oval', 'curly'];

    public function load(ObjectManager $manager): void
    {
        $skinColors = $this->createNamedEntities($manager, Skincolor::class, self::SKIN_COLORS, FixtureReferences::SKIN_COLORS);
        $sizes = $this->createNamedEntities($manager, Bodysize::class, self::BODY_SIZES, FixtureReferences::BODY_SIZES);
        $morphologies = $this->createNamedEntities($manager, Morphologie::class, self::MORPHOLOGIES, FixtureReferences::MORPHOLOGIES);

        foreach (self::MORPHOTYPES as $index => $name) {
            $morphotype = (new Morphotype())
                ->setName($name)
                ->setSize($sizes[$index])
                ->setMorphologie($morphologies[$index]);

            $this->persistTouched($manager, $morphotype);
            $this->addReference(FixtureReferences::MORPHOTYPES.$index, $morphotype);
        }

        $this->createNamedEntities($manager, Eyebrowscolor::class, ['brown', 'blond', 'black'], FixtureReferences::EYEBROWS_COLORS);
        $this->createNamedEntities($manager, Eyebrowshape::class, ['straight', 'angled', 'thin'], FixtureReferences::EYEBROWS_SHAPES);
        $this->createNamedEntities($manager, Eyecolor::class, ['blue', 'green', 'brown'], FixtureReferences::EYE_COLORS);
        $this->createNamedEntities($manager, Eyeshape::class, ['round', 'almond', 'small'], FixtureReferences::EYE_SHAPES);
        $this->createNamedEntities($manager, Faceshape::class, ['oval', 'square', 'round'], FixtureReferences::FACE_SHAPES);
        $this->createNamedEntities($manager, Hairscolor::class, ['black', 'brown', 'red'], FixtureReferences::HAIR_COLORS);
        $this->createNamedEntities($manager, Hairshape::class, ['short', 'long', 'curly'], FixtureReferences::HAIR_SHAPES);
        $this->createNamedEntities($manager, Mouthscolor::class, ['pink', 'red', 'natural'], FixtureReferences::MOUTH_COLORS);
        $this->createNamedEntities($manager, Mouthshape::class, ['smile', 'neutral', 'large'], FixtureReferences::MOUTH_SHAPES);
        $this->createNamedEntities($manager, Noseshape::class, ['small', 'straight', 'button'], FixtureReferences::NOSE_SHAPES);

        $manager->flush();
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @return list<T>
     */
    private function createNamedEntities(ObjectManager $manager, string $className, array $names, string $referencePrefix): array
    {
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
