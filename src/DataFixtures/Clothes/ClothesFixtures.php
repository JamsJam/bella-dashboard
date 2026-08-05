<?php

namespace App\DataFixtures\Clothes;

use App\Enum\ClotheStatus;
use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use App\Entity\Collections\Collections;
use App\Entity\MeasurementType;
use App\Entity\SizeGuide;
use App\Entity\SizeGuideMeasurement;
use App\Entity\SizeGuideSize;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

final class ClothesFixtures extends AbstractBaseFixtures implements FixtureGroupInterface
{
    public const CATEGORIES = [
        'T-shirts',
        'Sweats',
        'Accessoires',
    ];

    public const COLLECTIONS = [
        'Bella Basic',
        'Urban Mood',
        'Soft Club',
        'Island Days',
        'Tropical Line',
    ];

    public const COLORS = [
        ['name' => 'black', 'hex' => '111111'],
        ['name' => 'white', 'hex' => 'ffffff'],
        ['name' => 'pink', 'hex' => 'f4a6b8'],
        ['name' => 'blue', 'hex' => '2f5fdf'],
    ];

    public const SIZES = ['XS', 'S', 'M', 'L'];

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
    ];

    private const BOTTOM_GUIDE = [
        'XS' => ['waist_width' => 34, 'hip_width' => 48, 'inseam_length' => 74, 'pants_length' => 98],
        'S' => ['waist_width' => 37, 'hip_width' => 51, 'inseam_length' => 76, 'pants_length' => 100],
        'M' => ['waist_width' => 40, 'hip_width' => 54, 'inseam_length' => 78, 'pants_length' => 102],
        'L' => ['waist_width' => 43, 'hip_width' => 57, 'inseam_length' => 80, 'pants_length' => 104],
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
                ->setCode($code)
                ->setLabel($label)
                ->setPosition($position++)
                ->setIsActive(true);

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
     * @param list<Collections> $collections
     * @param list<Clothescolor> $colors
     * @param list<Clothessize> $sizes
     * @param array<string, MeasurementType> $measurementTypes
     */
    private function createClothes(ObjectManager $manager, array $collections, array $colors, array $sizes, array $measurementTypes): void
    {
        $referenceIndex = 0;

        foreach ($collections as $collection) {
            foreach ($colors as $color) {
                $name = sprintf('%s %s', $collection->getName(), $color->getName());
                $slug = $this->slug($name);
                $sizeGuide = $this->createSizeGuide(
                    $manager,
                    $this->isBottomCollection($collection) ? self::BOTTOM_GUIDE : self::TOP_GUIDE,
                    $measurementTypes,
                );
                $description = $this->faker->sentence(12);
                $metaDescription = sprintf('%s en %s', $collection->getName(), $color->getName());
                $isBestseller = $referenceIndex % 5 === 0;
                $isInCarousel = $referenceIndex % 7 === 0;

                $clothe = (new Clothes())
                    ->setName($name)
                    ->setPrice($this->faker->randomElement([1990, 2490, 2990, 3990]))
                    ->setCollection($collection);

                foreach ($sizes as $size) {
                    $variantName = trim(sprintf('%s %s', $name, (string) $size->getName()));
                    $variantSlug = $this->slug($name);
                    $variantImages = [
                        sprintf('/fixtures/clothes/%s/%s/front.png', $slug, strtolower((string) $size->getName())),
                        sprintf('/fixtures/clothes/%s/%s/back.png', $slug, strtolower((string) $size->getName())),
                    ];
                    $variant = (new ClothesVariant())
                        ->setName($variantName)
                        ->setSlug($variantSlug)
                        ->setColor($color)
                        ->setSize($size)
                        ->setSizeGuide($sizeGuide)
                        ->setSku(sprintf('%s-%s-%s', strtoupper($this->slug($name)), strtoupper($this->slug((string) $color->getName())), strtoupper($this->slug((string) $size->getName()))))
                        ->setStock($this->faker->numberBetween(0, 50))
                        ->setDescription($description)
                        ->setMetadescription($metaDescription)
                        ->setImages($variantImages)
                        ->setHighlightImage($variantImages[0])
                        ->setBestsellerImage($variantImages[0])
                        ->setIsBestseller($isBestseller)
                        ->setIsInCarousel($isInCarousel)
                        ->setPublicationStatus(ClotheStatus::Online);

                    $clothe->addVariant($variant);
                    $this->addReference(FixtureReferences::CLOTHES.$referenceIndex, $clothe);
                    $this->addReference(FixtureReferences::CLOTHES_VARIANTS.$referenceIndex, $variant);
                    $referenceIndex++;
                }

                $this->persistTouched($manager, $clothe);
            }
        }
    }

    public static function getGroups(): array
    {
        return ['commerce-test'];
    }

    /**
     * @param array<string, array<string, int|float>> $guideData
     * @param array<string, MeasurementType> $measurementTypes
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
