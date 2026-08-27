<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Faces\FaceAccessory;
use App\Entity\Avatar\Faces\Faces;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class FaceFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (AvatarFilterFixtures::SKIN_COLORS as $skinColorIndex => $skinColorName) {
            foreach (AvatarFilterFixtures::FACE_SHAPES as $shapeIndex => $shapeName) {
                $this->createFace(
                    $manager,
                    $skinColorIndex,
                    $skinColorName,
                    $shapeIndex,
                    $shapeName,
                );

                $this->createFace(
                    $manager,
                    $skinColorIndex,
                    $skinColorName,
                    $shapeIndex,
                    $shapeName,
                    $this->getReference(FixtureReferences::FACE_ACCESSORIES.$shapeIndex, FaceAccessory::class),
                    AvatarFilterFixtures::FACE_ACCESSORIES[$shapeIndex],
                );
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [AvatarFilterFixtures::class];
    }

    private function createFace(
        ObjectManager $manager,
        int $skinColorIndex,
        string $skinColorName,
        int $shapeIndex,
        string $shapeName,
        ?FaceAccessory $accessory = null,
        string $accessoryName = '-none-',
    ): void {
        $name = sprintf('face__%s__%s__%s', $skinColorName, $shapeName, $accessoryName);

        $face = (new Faces())
            ->setName($name)
            ->setSkincolor($this->getReference(
                FixtureReferences::SKIN_COLORS.$skinColorIndex,
                \App\Entity\Avatar\Skincolor::class,
            ))
            ->setShape($this->getReference(
                FixtureReferences::FACE_SHAPES.$shapeIndex,
                \App\Entity\Avatar\Faces\Faceshape::class,
            ))
            ->setAccessory($accessory)
            ->setImage($this->fakeAvatarPngPath('face', $name))
            ->setChecksum($this->fakeChecksum());

        $this->persistTouched($manager, $face);
    }
}
