<?php

namespace App\DataFixtures\Users;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Users\Customers;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

final class CustomerFixtures extends AbstractBaseFixtures implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 20; $i++) {
            $customer = (new Customers())
                ->setEmail($i === 0 ? 'customer@example.com' : $this->faker->unique()->safeEmail())
                ->setRoles(['ROLE_USER']);

            $customer->setPassword($this->passwordHasher->hashPassword($customer, 'password'));

            $this->persistTouched($manager, $customer);
            $this->addReference(FixtureReferences::CUSTOMERS.$i, $customer);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['commerce-test'];
    }
}
