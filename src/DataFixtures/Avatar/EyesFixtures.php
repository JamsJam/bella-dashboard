<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Eyes\Eye;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class EyesFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (AvatarFilterFixtures::EYE_COLORS as $colorIndex => $colorName) {
            foreach (AvatarFilterFixtures::EYE_SHAPES as $shapeIndex => $shapeName) {
                $name = sprintf('eye__%s__%s', $colorName, $shapeName);

                $eye = (new Eye())
                    ->setName($name)
                    ->setColor($this->getReference(
                        FixtureReferences::EYE_COLORS . $colorIndex,
                        \App\Entity\Avatar\Eyes\Eyecolor::class,
                    ))
                    ->setShape($this->getReference(
                        FixtureReferences::EYE_SHAPES . $shapeIndex,
                        \App\Entity\Avatar\Eyes\Eyeshape::class,
                    ))
                    ->setImage($this->fakeAvatarPngPath('eyes', $name))
                    ->setChecksum($this->fakeChecksum());

                $this->persistTouched($manager, $eye);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
