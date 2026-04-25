<?php

namespace App\DataFixtures;

use App\Entity\Clothes\Clothescolor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ClotheColorFixtures extends Fixture
{
    public const CLOTHE_COLOR_REFERENCE = 'clothecolor_' ;

    public function load(ObjectManager $manager): void
    {
        $today = new \DateTimeImmutable();
        $colors = [
            ['name' => 'Rouge', 'hexa' => 'FF0000'],
            ['name' => 'Vert', 'hexa' => '00FF00'],
            ['name' => 'Bleu', 'hexa' => '0000FF'],
            ['name' => 'Noir', 'hexa' => '000000'],
            ['name' => 'Blanc', 'hexa' => 'FFFFFF'],
            ['name' => 'Gris', 'hexa' => '808080'],
            ['name' => 'Jaune', 'hexa' => 'FFFF00'],
            ['name' => 'Rose', 'hexa' => 'FFC0CB'],
        ];

        foreach ($colors as $index => $data) {
            $color = new Clothescolor();
            $color->setName($data['name'])
                ->setHexa($data['hexa'])
                ->setCreatedAt($today)
                ->setEditedAt($today)
            ;
            $manager->persist($color);

            // 🔑 Pour pouvoir réutiliser dans ClothesFixtures
            $this->addReference(self::CLOTHE_COLOR_REFERENCE . $index, $color);
        }

        $manager->flush();
    }
}
