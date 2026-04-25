<?php

namespace App\DataFixtures;

use App\Entity\Clothes\Clothes;
use App\Entity\Category\Category;
use App\Entity\Clothes\Clothessize;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Collections\Collections;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Faker\Factory;

class CategoryFixtures extends Fixture
{
    public const CATEGORY_REFERENCE = 'category_';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $today = new \DateTimeImmutable();

        $categoryName = $faker->unique()->word();
        $slug = strtolower(str_replace(' ', '-', $categoryName));

        for ($i = 1; $i <= 10; $i++) {
            $category = new Category();
            $category->setName($faker->unique()->word(2,true))
                ->setSlug($slug)
                ->setImage($faker->optional()->imageUrl(640, 480, 'cats', true))
                ->setMetaDescription($faker->optional()->sentence(10))
                ->setIsOnline($faker->boolean(80)) // 80% en ligne
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
            $manager->persist($category);

            $this->addReference(self::CATEGORY_REFERENCE . $i, $category);
        }

        $manager->flush();

    }
}
