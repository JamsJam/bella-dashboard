<?php

namespace App\DataFixtures\Users;

use App\DataFixtures\AbstractBaseFixtures;
use App\Entity\Users\Admin;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminFixtures extends AbstractBaseFixtures implements FixtureGroupInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    public function load(ObjectManager $manager): void
    {
        $admin = (new Admin())
            ->setEmail('admin@admin.fr')
            ->setRoles(['ROLE_ADMIN']);

        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'Admin1234'),
        );

        $this->persistTouched($manager, $admin);
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['commerce-test'];
    }
}
