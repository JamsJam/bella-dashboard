<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Category\Category;
use App\DataFixtures\CategoryFixtures;
use App\Entity\Collections\Collections;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CollectionsFixtures extends Fixture implements DependentFixtureInterface

{
    public const COLLECTION_REFERENCE = 'collection_';
    
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $today = new \DateTimeImmutable();
        // On suppose que CategoryFixtures a créé 10 catégories
        for ($i = 1; $i <= 30; $i++) {
            $collection = new Collections();
            $collection->setName($faker->words(2, true)) // 2 mots aléatoires
                ->setIsOnline($faker->boolean(80))
                ->setImage($faker->optional()->imageUrl(640, 480, 'fashion', true))
                ->setSizeguid(null)
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
                ;

            // Relier à une catégorie existante (créée par CategoryFixtures)
            /** @var Category $category */
            $category = $this->getReference(CategoryFixtures::CATEGORY_REFERENCE . $faker->numberBetween(1, 10), Category::class);
            $collection->setCategory($category);

            $manager->persist($collection);

            $this->addReference(self::COLLECTION_REFERENCE . $i, $collection);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}
