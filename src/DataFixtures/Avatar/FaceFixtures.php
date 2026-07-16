<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Faces\Faces;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FaceFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (['oval', 'square', 'round'] as $index => $shapeName) {
            $skinColorIndex = $index % 3;
            $name = sprintf('visage__%s__%s__-none-', AvatarFilterFixtures::SKIN_COLORS[$skinColorIndex], $shapeName);

            $face = (new Faces())
                ->setName($name)
                ->setSkincolor($this->getReference(FixtureReferences::SKIN_COLORS.$skinColorIndex, \App\Entity\Avatar\Skincolor::class))
                ->setShape($this->getReference(FixtureReferences::FACE_SHAPES.$index, \App\Entity\Avatar\Faces\Faceshape::class))
                ->setImage($this->fakeAvatarPngPath('visage', $name))
                ->setChecksum($this->fakeChecksum());

            $this->persistTouched($manager, $face);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
