<?php

namespace App\DataFixtures;

use App\Entity\Clothes\Clothessize;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ClotheSizeFixtures extends Fixture
{
    public const CLOTHE_SIZE_REFERENCE = 'clothesize_' ;

    public function load(ObjectManager $manager): void
    {
        $today = new \DateTimeImmutable();
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        foreach ($sizes as $index => $sizeName) {
            $size = new Clothessize();
            $size->setName($sizeName)
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;

            $manager->persist($size);

            // 🔑 Pour réutiliser dans ClothesFixtures
            $this->addReference(self::CLOTHE_SIZE_REFERENCE  . $index, $size);
        }

        $manager->flush();
    }
}
