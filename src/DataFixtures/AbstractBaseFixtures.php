<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\String\Slugger\AsciiSlugger;

abstract class AbstractBaseFixtures extends Fixture
{
    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    protected function persistTouched(ObjectManager $manager, object $entity): void
    {
        $this->touch($entity);
        $manager->persist($entity);
    }

    protected function touch(object $entity): void
    {
        $date = \DateTimeImmutable::createFromMutable($this->faker->dateTimeBetween('-6 months'));

        if (method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt($date);
        }

        if (method_exists($entity, 'setEditedAt')) {
            $entity->setEditedAt($date);
        }
    }

    protected function slug(string $value): string
    {
        return strtolower((string) (new AsciiSlugger())->slug($value));
    }

    protected function fakeChecksum(): string
    {
        return hash('sha256', $this->faker->uuid());
    }

    protected function fakeAvatarPngPath(string $part, string $name): string
    {
        return sprintf('/fixtures/avatar/%s/%s.png', $part, $this->slug($name));
    }
}
