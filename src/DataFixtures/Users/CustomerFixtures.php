<?php

namespace App\DataFixtures\Users;

use App\DataFixtures\AbstractBaseFixtures;
use App\DataFixtures\FixtureReferences;
use App\Entity\Users\Customers;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CustomerFixtures extends AbstractBaseFixtures
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 12; $i++) {
            $customer = (new Customers())
                ->setEmail($i === 0 ? 'customer@example.com' : $this->faker->unique()->safeEmail())
                ->setRoles(['ROLE_USER']);

            $customer->setPassword($this->passwordHasher->hashPassword($customer, 'password'));

            $this->persistTouched($manager, $customer);
            $this->addReference(FixtureReferences::CUSTOMERS.$i, $customer);
        }

        $manager->flush();
    }
}
