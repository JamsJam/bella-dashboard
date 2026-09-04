<?php

namespace App\State\Users;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Users\Customers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<Customers, Customers>
 */
final readonly class CustomerPasswordHasherProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Customers
    {
        if (!$data instanceof Customers) {
            throw new \InvalidArgumentException('Invalid customer payload.');
        }

        $data->setPassword($this->passwordHasher->hashPassword($data, (string) $data->getPassword()));

        if (method_exists($data, 'setCreatedAt')) {
            $data->setCreatedAt(new \DateTimeImmutable());
        }

        if (method_exists($data, 'setEditedAt')) {
            $data->setEditedAt(new \DateTimeImmutable());
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
