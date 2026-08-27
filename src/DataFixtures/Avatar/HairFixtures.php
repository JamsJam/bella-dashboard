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
        foreach (AvatarFilterFixtures::HAIR_COLORS as $index => $colorName) {
            $shapeName = AvatarFilterFixtures::HAIR_SHAPES[$index];
            $name = sprintf('hair__%s__%s', $colorName, $shapeName);

            $hair = (new Hairs())
                ->setName($name)
                ->setColor($this->getReference(
                    FixtureReferences::HAIR_COLORS.$index,
                    \App\Entity\Avatar\Hairs\Hairscolor::class,
                ))
                ->setShape($this->getReference(
                    FixtureReferences::HAIR_SHAPES.$index,
                    \App\Entity\Avatar\Hairs\Hairshape::class,
                ))
                ->setImages([
                    'front' => $this->fakeAvatarPngPath('hair', $name.'__front'),
                    'back' => $this->fakeAvatarPngPath('hair', $name.'__back'),
                ])
                ->setChecksum($this->fakeChecksum());

            $this->persistTouched($manager, $hair);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
