<?php

namespace App\DataFixtures\Avatar;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\Clothes\ClothesFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Body\Morphotype;
use App\Entity\Avatar\Skincolor;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

final class BodyFixtures extends AbstractBaseFixtures implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $clothesVariantGroups = $this->getClothesVariantGroups();

        foreach (AvatarFilterFixtures::SKIN_COLORS as $skinColorIndex => $skinColorName) {
            /** @var Skincolor $skinColor */
            $skinColor = $this->getReference(FixtureReferences::SKIN_COLORS.$skinColorIndex, Skincolor::class);

            foreach (AvatarFilterFixtures::MORPHOLOGIES as $morphologyIndex => $morphologieName) {
                foreach (AvatarFilterFixtures::BODY_SIZES as $sizeIndex => $bodySizeName) {
                    $morphotypeIndex = $morphologyIndex * count(AvatarFilterFixtures::BODY_SIZES) + $sizeIndex;
                    /** @var Morphotype $morphotype */
                    $morphotype = $this->getReference(FixtureReferences::MORPHOTYPES.$morphotypeIndex, Morphotype::class);

                    $this->createBody(
                        manager: $manager,
                        skinColor: $skinColor,
                        skinColorName: $skinColorName,
                        morphotype: $morphotype,
                        morphologieName: $morphologieName,
                        bodySizeName: $bodySizeName,
                        clotheSlug: '-none-',
                        clothesVariants: [],
                    );

                    foreach ($clothesVariantGroups as $clotheSlug => $clothesVariants) {
                        $this->createBody(
                            manager: $manager,
                            skinColor: $skinColor,
                            skinColorName: $skinColorName,
                            morphotype: $morphotype,
                            morphologieName: $morphologieName,
                            bodySizeName: $bodySizeName,
                            clotheSlug: $clotheSlug,
                            clothesVariants: $clothesVariants,
                        );
                    }
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
     * Une variante de vêtement d'avatar est un groupe couleur partageant le même slug.
     * Toutes les tailles de ce groupe sont liées aux mêmes corps.
     *
     * @return array<string, list<ClothesVariant>>
     */
    private function getClothesVariantGroups(): array
    {
        $groups = [];

        foreach (ClothesFixtures::CLOTHES as $clotheIndex => $unused) {
            $clothe = $this->getReference(FixtureReferences::CLOTHES.$clotheIndex, Clothes::class);

            foreach ($clothe->getVariants() as $variant) {
                $slug = (string) $variant->getSlug();
                $groups[$slug][] = $variant;
            }
        }

        return $groups;
    }

    /** @param list<ClothesVariant> $clothesVariants */
    private function createBody(
        ObjectManager $manager,
        Skincolor $skinColor,
        string $skinColorName,
        Morphotype $morphotype,
        string $morphologieName,
        string $bodySizeName,
        string $clotheSlug,
        array $clothesVariants,
    ): void {
        $name = sprintf(
            'body__%s__%s__%s__%s',
            $skinColorName,
            $morphologieName,
            strtolower($bodySizeName),
            $clotheSlug,
        );

        $body = (new Body())
            ->setName($name)
            ->setSkincolor($skinColor)
            ->setMorphotype($morphotype)
            ->setImage($this->fakeAvatarPngPath('body', $name))
            ->setChecksum($this->fakeChecksum());

        foreach ($clothesVariants as $variant) {
            $body->addClothesVariant($variant);
        }

        $this->persistTouched($manager, $body);
    }
}
