<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Hairs\Hairs;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class HairFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (['black', 'brown', 'red'] as $colorIndex => $colorName) {
            foreach (['short', 'long', 'curly'] as $shapeIndex => $shapeName) {
                $name = sprintf('hair__%s__%s', $colorName, $shapeName);

                $hair = (new Hairs())
                    ->setName($name)
                    ->setColor($this->getReference(FixtureReferences::HAIR_COLORS.$colorIndex, \App\Entity\Avatar\Hairs\Hairscolor::class))
                    ->setShape($this->getReference(FixtureReferences::HAIR_SHAPES.$shapeIndex, \App\Entity\Avatar\Hairs\Hairshape::class))
                    ->setImages([
                        'front' => $this->fakeAvatarPngPath('hair', $name.'__front'),
                        'back' => $this->fakeAvatarPngPath('hair', $name.'__back'),
                    ])
                    ->setChecksum($this->fakeChecksum());

                $this->persistTouched($manager, $hair);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
