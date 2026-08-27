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
        foreach (AvatarFilterFixtures::MOUTH_COLORS as $index => $colorName) {
            $shapeName = AvatarFilterFixtures::MOUTH_SHAPES[$index];
            $name = sprintf('mouth__%s__%s', $colorName, $shapeName);

            $mouth = (new Mouths())
                ->setName($name)
                ->setColor($this->getReference(
                    FixtureReferences::MOUTH_COLORS.$index,
                    \App\Entity\Avatar\Mouths\Mouthscolor::class,
                ))
                ->setShape($this->getReference(
                    FixtureReferences::MOUTH_SHAPES.$index,
                    \App\Entity\Avatar\Mouths\Mouthshape::class,
                ))
                ->setImage($this->fakeAvatarPngPath('mouth', $name))
                ->setChecksum($this->fakeChecksum());

            $this->persistTouched($manager, $mouth);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
