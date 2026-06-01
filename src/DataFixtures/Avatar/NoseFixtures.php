<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Noses\Nose;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class NoseFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (['small', 'straight', 'button'] as $shapeIndex => $shapeName) {
            foreach ([0, 1] as $skinColorIndex) {
                $name = sprintf('nose__%s__%s', AvatarFilterFixtures::SKIN_COLORS[$skinColorIndex], $shapeName);

                $nose = (new Nose())
                    ->setName($name)
                    ->setSkincolor($this->getReference(FixtureReferences::SKIN_COLORS.$skinColorIndex, \App\Entity\Avatar\Skincolor::class))
                    ->setShape($this->getReference(FixtureReferences::NOSE_SHAPES.$shapeIndex, \App\Entity\Avatar\Noses\Noseshape::class))
                    ->setImage($this->fakeAvatarPngPath('nose', $name))
                    ->setChecksum($this->fakeChecksum());

                $this->persistTouched($manager, $nose);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
