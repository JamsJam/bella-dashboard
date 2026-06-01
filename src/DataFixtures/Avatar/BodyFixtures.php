<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\Clothes\ClothesFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Skincolor;
use App\Entity\Clothes\Clothes;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class BodyFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        foreach (AvatarFilterFixtures::SKIN_COLORS as $skinColorIndex => $skinColorName) {
            /** @var Skincolor $skinColor */
            $skinColor = $this->getReference(FixtureReferences::SKIN_COLORS.$skinColorIndex, Skincolor::class);

            foreach (AvatarFilterFixtures::MORPHOTYPES as $morphotypeIndex => $morphotypeName) {
                /** @var Morphotype $morphotype */
                $morphotype = $this->getReference(FixtureReferences::MORPHOTYPES.$morphotypeIndex, Morphotype::class);
                $morphologieName = AvatarFilterFixtures::MORPHOLOGIES[$morphotypeIndex];
                $compatibleClothes = $this->getCompatibleClothes($morphotype);

                $this->createBody(
                    manager: $manager,
                    skinColor: $skinColor,
                    skinColorName: $skinColorName,
                    morphotype: $morphotype,
                    morphologieName: $morphologieName,
                    morphotypeName: $morphotypeName,
                    clothe: null,
                    clotheSlug: '-none-',
                );

                foreach ($compatibleClothes as $clothe) {
                    $this->createBody(
                        manager: $manager,
                        skinColor: $skinColor,
                        skinColorName: $skinColorName,
                        morphotype: $morphotype,
                        morphologieName: $morphologieName,
                        morphotypeName: $morphotypeName,
                        clothe: $clothe,
                        clotheSlug: (string) $clothe->getSlug(),
                    );
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AvatarFilterFixtures::class,
            ClothesFixtures::class,
        ];
    }

    /**
     * @return list<Clothes>
     */
    private function getCompatibleClothes(Morphotype $morphotype): array
    {
        $sizeName = $morphotype->getSize()?->getName();
        $sizeIndex = array_search($sizeName, ClothesFixtures::SIZES, true);

        if ($sizeIndex === false) {
            return [];
        }

        $clothes = [];
        $collectionCount = count(ClothesFixtures::COLLECTIONS);
        $colorCount = count(ClothesFixtures::COLORS);
        $sizeCount = count(ClothesFixtures::SIZES);

        for ($collectionIndex = 0; $collectionIndex < $collectionCount; $collectionIndex++) {
            for ($colorIndex = 0; $colorIndex < $colorCount; $colorIndex++) {
                $referenceIndex = (($collectionIndex * $colorCount + $colorIndex) * $sizeCount) + $sizeIndex;
                $clothes[] = $this->getReference(FixtureReferences::CLOTHES.$referenceIndex, Clothes::class);
            }
        }

        return $clothes;
    }

    private function createBody(
        ObjectManager $manager,
        Skincolor $skinColor,
        string $skinColorName,
        Morphotype $morphotype,
        string $morphologieName,
        string $morphotypeName,
        ?Clothes $clothe,
        string $clotheSlug,
    ): void {
        $name = sprintf(
            'body__%s__%s__%s__%s',
            $skinColorName,
            $morphologieName,
            $morphotypeName,
            $clotheSlug,
        );

        $body = (new Body())
            ->setName($name)
            ->setSkincolor($skinColor)
            ->setMorphotype($morphotype)
            ->setClothe($clothe)
            ->setImage($this->fakeAvatarPngPath('body', $name))
            ->setChecksum($this->fakeChecksum());

        $this->persistTouched($manager, $body);
    }
}
