<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Mouths\Mouths;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class MouthFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (['pink', 'red', 'natural'] as $colorIndex => $colorName) {
            foreach (['smile', 'neutral'] as $shapeIndex => $shapeName) {
                $name = sprintf('mouth__%s__%s', $colorName, $shapeName);

                $mouth = (new Mouths())
                    ->setName($name)
                    ->setColor($this->getReference(FixtureReferences::MOUTH_COLORS.$colorIndex, \App\Entity\Avatar\Mouths\Mouthscolor::class))
                    ->setShape($this->getReference(FixtureReferences::MOUTH_SHAPES.$shapeIndex, \App\Entity\Avatar\Mouths\Mouthshape::class))
                    ->setImage($this->fakeAvatarPngPath('mouth', $name))
                    ->setChecksum($this->fakeChecksum());

                $this->persistTouched($manager, $mouth);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
