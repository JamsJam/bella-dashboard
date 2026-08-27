<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Eyebrows\Eyebrows;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class EyebrowsFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (AvatarFilterFixtures::EYEBROW_COLORS as $index => $colorName) {
            $shapeName = AvatarFilterFixtures::EYEBROW_SHAPES[$index];
            $name = sprintf('eyebrows__%s__%s', $colorName, $shapeName);

            $eyebrows = (new Eyebrows())
                ->setName($name)
                ->setColor($this->getReference(
                    FixtureReferences::EYEBROWS_COLORS.$index,
                    \App\Entity\Avatar\Eyebrows\Eyebrowscolor::class,
                ))
                ->setShape($this->getReference(
                    FixtureReferences::EYEBROWS_SHAPES.$index,
                    \App\Entity\Avatar\Eyebrows\Eyebrowshape::class,
                ))
                ->setImage($this->fakeAvatarPngPath('eyebrows', $name))
                ->setChecksum($this->fakeChecksum());

            $this->persistTouched($manager, $eyebrows);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }
}
