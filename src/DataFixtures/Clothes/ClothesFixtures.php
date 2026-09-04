<?php

namespace App\DataFixtures\Clothes;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Collections\Collections;
use App\Entity\MeasurementType;
use App\Entity\SizeGuide;
use App\Entity\SizeGuideMeasurement;
use App\Entity\SizeGuideSize;
use App\Enum\ClotheStatus;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class ClothesFixtures extends AbstractBaseFixtures implements FixtureGroupInterface
{
    public const CATEGORIES = [
        'Chemises',
    ];

    public const COLLECTIONS = [
        'Été',
    ];

    public const CLOTHES = [
        'Chemise en lin',
        'Chemise cubaine',
        'Chemise oversize',
        'Chemise à rayures',
        'Chemise florale',
    ];

    public const COLORS = [
        ['name' => 'black', 'hex' => '111111'],
        ['name' => 'white', 'hex' => 'ffffff'],
        ['name' => 'pink', 'hex' => 'f4a6b8'],
        ['name' => 'blue', 'hex' => '2f5fdf'],
        ['name' => 'green', 'hex' => '3f8f62'],
        ['name' => 'yellow', 'hex' => 'f4c542'],
    ];

    public const SIZES = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

    /** Nombre de couleurs pour chacun des cinq vêtements. */
    public const COLOR_COUNTS_BY_CLOTHE = [2, 3, 4, 5, 6];

    private const MEASUREMENT_TYPES = [
        'sleeve_length' => 'Longueur de manche',
        'chest_width' => 'Poitrine',
        'shoulder_width' => 'Epaules',
        'body_length' => 'Longueur',
        'waist_width' => 'Taille',
        'hip_width' => 'Hanche',
        'inseam_length' => 'Entrejambe',
        'pants_length' => 'Longueur pantalon',
    ];

    private const TOP_GUIDE = [
        'XS' => ['sleeve_length' => 19, 'chest_width' => 46, 'shoulder_width' => 40, 'body_length' => 66],
        'S' => ['sleeve_length' => 20, 'chest_width' => 49, 'shoulder_width' => 42, 'body_length' => 68],
        'M' => ['sleeve_length' => 21, 'chest_width' => 52, 'shoulder_width' => 44, 'body_length' => 70],
        'L' => ['sleeve_length' => 22, 'chest_width' => 55, 'shoulder_width' => 46, 'body_length' => 72],
        'XL' => ['sleeve_length' => 23, 'chest_width' => 58, 'shoulder_width' => 48, 'body_length' => 74],
        'XXL' => ['sleeve_length' => 24, 'chest_width' => 61, 'shoulder_width' => 50, 'body_length' => 76],
    ];

    private const BOTTOM_GUIDE = [
        'XS' => ['waist_width' => 34, 'hip_width' => 48, 'inseam_length' => 74, 'pants_length' => 98],
        'S' => ['waist_width' => 37, 'hip_width' => 51, 'inseam_length' => 76, 'pants_length' => 100],
        'M' => ['waist_width' => 40, 'hip_width' => 54, 'inseam_length' => 78, 'pants_length' => 102],
        'L' => ['waist_width' => 43, 'hip_width' => 57, 'inseam_length' => 80, 'pants_length' => 104],
        'XL' => ['waist_width' => 46, 'hip_width' => 60, 'inseam_length' => 82, 'pants_length' => 106],
        'XXL' => ['waist_width' => 49, 'hip_width' => 63, 'inseam_length' => 84, 'pants_length' => 108],
    ];

    public function load(ObjectManager $manager): void
    {
        $categories = $this->createCategories($manager);
        $collections = $this->createCollections($manager, $categories);
        $colors = $this->createColors($manager);
        $sizes = $this->createSizes($manager);
        $measurementTypes = $this->createMeasurementTypes($manager);

        $this->createClothes($manager, $collections, $colors, $sizes, $measurementTypes);

        $manager->flush();
    }

    /**
     * @return array<string, MeasurementType>
     */
    private function createMeasurementTypes(ObjectManager $manager): array
    {
        $types = [];
        $position = 0;

        foreach (self::MEASUREMENT_TYPES as $code => $label) {
            $type = (new MeasurementType())
                ->setLabel($label)
                ->setPosition($position++);

            $manager->persist($type);
            $types[$code] = $type;
        }

        return $types;
    }

    /**
     * @return list<Category>
     */
    private function createCategories(ObjectManager $manager): array
    {
        $categories = [];

        foreach (self::CATEGORIES as $index => $name) {
            $category = (new Category())
                ->setName($name)
                ->setSlug($this->slug($name))
                ->setImage(sprintf('/fixtures/clothes/categories/%s.png', $this->slug($name)))
                ->setMetaDescription(sprintf('Categorie %s', $name))
                ->setIsOnline(true);

            $this->persistTouched($manager, $category);
            $this->addReference(FixtureReferences::CLOTHES_CATEGORIES.$index, $category);
            $categories[] = $category;
        }

        return $categories;
    }

    /**
     * @param list<Category> $categories
     *
     * @return list<Collections>
     */
    private function createCollections(ObjectManager $manager, array $categories): array
    {
        $collections = [];

        foreach (self::COLLECTIONS as $index => $name) {
            $collection = (new Collections())
                ->setName($name)
                ->setCategory($categories[$index % count($categories)])
                ->setImage(sprintf('/fixtures/clothes/collections/%s.png', $this->slug($name)))
                ->setIsOnline(true);

            $this->persistTouched($manager, $collection);
            $this->addReference(FixtureReferences::COLLECTIONS.$index, $collection);
            $collections[] = $collection;
        }

        return $collections;
    }

    /**
     * @return list<Clothescolor>
     */
    private function createColors(ObjectManager $manager): array
    {
        $colors = [];

        foreach (self::COLORS as $index => $data) {
            $color = (new Clothescolor())
                ->setName($data['name'])
                ->setHexa($data['hex']);

            $this->persistTouched($manager, $color);
            $this->addReference(FixtureReferences::CLOTHES_COLORS.$index, $color);
            $colors[] = $color;
        }

        return $colors;
    }

    /**
     * @return list<Clothessize>
     */
    private function createSizes(ObjectManager $manager): array
    {
        $sizes = [];

        foreach (self::SIZES as $index => $name) {
            $size = (new Clothessize())->setName($name);

            $this->persistTouched($manager, $size);
            $this->addReference(FixtureReferences::CLOTHES_SIZES.$index, $size);
            $sizes[] = $size;
        }

        return $sizes;
    }

    /**
     * @param list<Collections>              $collections
     * @param list<Clothescolor>             $colors
     * @param list<Clothessize>              $sizes
     * @param array<string, MeasurementType> $measurementTypes
     */
    private function createClothes(
        ObjectManager $manager,
        array $collections,
        array $colors,
        array $sizes,
        array $measurementTypes,
    ): void {
        $variantReferenceIndex = 0;

        foreach (self::CLOTHES as $clotheIndex => $clotheName) {
            $collection = $collections[0];
            $sizeGuide = $this->createSizeGuide(
                $manager,
                $this->isBottomCollection($collection) ? self::BOTTOM_GUIDE : self::TOP_GUIDE,
                $measurementTypes,
            );
            $clothe = (new Clothes())
                ->setName($clotheName)
                ->setPrice($this->faker->randomElement([1990, 2490, 2990, 3990]))
                ->setCollection($collection);

            foreach (array_slice($colors, 0, self::COLOR_COUNTS_BY_CLOTHE[$clotheIndex]) as $color) {
                $colorName = (string) $color->getName();
                $variantSlug = $this->slug(sprintf('%s %s', $clotheName, $colorName));
                $description = $this->faker->sentence(12);
                $metaDescription = sprintf('%s en %s', $clotheName, $colorName);
                $isBestseller = 0 === $variantReferenceIndex % 5;
                $isInCarousel = 0 === $variantReferenceIndex % 7;

                foreach ($sizes as $size) {
                    $sizeName = (string) $size->getName();
                    $variantName = trim(sprintf('%s %s %s', $clotheName, $colorName, $sizeName));
                    $variantImages = [
                        sprintf('/fixtures/clothes/%s/%s/front.png', $variantSlug, strtolower($sizeName)),
                        sprintf('/fixtures/clothes/%s/%s/back.png', $variantSlug, strtolower($sizeName)),
                    ];
                    $variant = (new ClothesVariant())
                        ->setName($variantName)
                        ->setSlug($variantSlug)
                        ->setColor($color)
                        ->setSize($size)
                        ->setSizeGuide($sizeGuide)
                        ->setSku(sprintf(
                            '%s-%s-%s',
                            strtoupper($this->slug($clotheName)),
                            strtoupper($this->slug($colorName)),
                            strtoupper($this->slug($sizeName)),
                        ))
                        ->setStock($this->faker->numberBetween(0, 50))
                        ->setDescription($description)
                        ->setMetadescription($metaDescription)
                        ->setImages($variantImages)
                        ->setHighlightImage($variantImages[0])
                        ->setBestsellerImage($variantImages[0])
                        ->setIsBestseller($isBestseller)
                        ->setIsInCarousel($isInCarousel)
                        ->setPublicationStatus(ClotheStatus::Online)
                        ->setPublishedAt(new \DateTimeImmutable());

                    $clothe->addVariant($variant);
                    $this->addReference(FixtureReferences::CLOTHES_VARIANTS.$variantReferenceIndex, $variant);
                    ++$variantReferenceIndex;
                }
            }

            $this->persistTouched($manager, $clothe);
            $this->addReference(FixtureReferences::CLOTHES.$clotheIndex, $clothe);
        }
    }

    public static function getGroups(): array
    {
        return ['commerce-test'];
    }

    /**
     * @param array<string, array<string, int|float>> $guideData
     * @param array<string, MeasurementType>          $measurementTypes
     */
    private function createSizeGuide(ObjectManager $manager, array $guideData, array $measurementTypes): SizeGuide
    {
        $guide = (new SizeGuide())->setUnit('cm');

        $position = 0;
        foreach ($guideData as $sizeLabel => $values) {
            $size = (new SizeGuideSize())
                ->setLabel($sizeLabel)
                ->setPosition($position++);

            foreach ($values as $typeCode => $value) {
                if (!isset($measurementTypes[$typeCode])) {
                    continue;
                }

                $size->addMeasurement(
                    (new SizeGuideMeasurement())
                        ->setType($measurementTypes[$typeCode])
                        ->setValue(number_format((float) $value, 2, '.', ''))
                        ->setUnit('cm'),
                );
            }

            $guide->addSize($size);
        }

        $this->persistTouched($manager, $guide);

        return $guide;
    }

    private function isBottomCollection(Collections $collection): bool
    {
        return str_contains(strtolower((string) $collection->getName()), 'urban');
    }
}
