<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Collections\Collections;
use Doctrine\Persistence\ObjectManager;
use App\DataFixtures\ClotheSizeFixtures;
use App\DataFixtures\ClotheColorFixtures;
use App\DataFixtures\CollectionsFixtures;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class ClotheFixtures extends Fixture implements DependentFixtureInterface

{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $today = new \DateTimeImmutable();
        $bestSelledLimit = 4;
        $bestSelledIndex = 0;
        $carouselLimit = 4;
        $carouselIndex = 0;
        // On suppose ~30 collections
        for ($i = 1; $i <= 30; $i++) {
            /** @var Collections $collection */
            $collection = $this->getReference(CollectionsFixtures::COLLECTION_REFERENCE . $i, Collections::class);

            // Choisir 2 à 3 couleurs par collection
            for ($c = 0; $c < $faker->numberBetween(2, 3); $c++) {
                /** @var Clothescolor $color */
                $color = $this->getReference(ClotheColorFixtures::CLOTHE_COLOR_REFERENCE . $faker->numberBetween(0, 7), Clothescolor::class);

                // Nom du vêtement = collection + couleur
                $clotheName = $collection->getName() . ' ' . $color->getName();
 

                // Slug identique pour toutes les tailles
                $slug = strtolower(str_replace(' ', '-', $clotheName));
                $isOnline=$faker->boolean(85);
                $status=$faker->randomElement(['new', 'in_stock', 'sold_out']);
                $clotheImages = [$faker->imageUrl(640, 480, 'fashion', true)];

                $isBestSeller = $bestSelledIndex < $bestSelledLimit ? $faker->boolean(30) : false;
                $isBestSeller && $bestSelledIndex++ ;
                
                $isInCarousel = $carouselIndex < $carouselLimit ? $faker->boolean(30) : false;
                $isInCarousel && $carouselIndex++ ;

                // Pour chaque taille dispo
                for ($s = 0; $s <= 5; $s++) {
                    /** @var Clothessize $size */
                    $sizeEntity = $this->getReference(ClotheSizeFixtures::CLOTHE_SIZE_REFERENCE . $s, Clothessize::class);

                    $clothe = new Clothes();
                    $clothe->setCollection($collection)
                        ->setColor($color)
                        ->setSize($sizeEntity)
                        ->setName($clotheName)
                        ->setSlug($slug)
                        ->setSku('SKU--' . $clotheName . '--' . strtoupper($color->getName()) . '--' . $sizeEntity->getName())
                        ->setDescription($faker->sentence(12))
                        ->setMetadescription($faker->sentence(8))
                        ->setPrice($faker->numberBetween(1000, 10000)) // entre 10€ et 100€
                        ->setStock($faker->numberBetween(5, 50))
                        ->setStatus($status)
                        ->setIsOnline($isOnline)
                        ->setImages($clotheImages)
                        ->setCreatedAt($today)
                        ->setEditedAt($today)
                        ->setIsBestseller($isBestSeller)
                        ->setIsInCarousel($isInCarousel)
                    ;

                    $manager->persist($clothe);
                }
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CollectionsFixtures::class,
            ClotheColorFixtures::class,
            ClotheSizeFixtures::class,
        ];
    }
}
