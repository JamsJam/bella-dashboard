<?php

namespace App\Application\Orders\Services;

use App\Entity\Orders\Orders;
use Doctrine\ORM\EntityManagerInterface;

final readonly class OrderReferenceGenerator
{
    private const ALPHANUMERIC_CHARACTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    private const RANDOM_PART_LENGTH = 8;
    private const MAX_ATTEMPTS = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function generate(): string
    {
        for ($attempt = 0; $attempt < self::MAX_ATTEMPTS; ++$attempt) {
            $reference = sprintf(
                'ORDER-%s-%s',
                (new \DateTimeImmutable())->format('Ymd'),
                $this->randomPart(),
            );

            if ($this->entityManager->getRepository(Orders::class)->findOneBy(['orderReference' => $reference]) === null) {
                return $reference;
            }
        }

        throw new \RuntimeException('Impossible de générer une référence de commande unique.');
    }

    private function randomPart(): string
    {
        $characters = '';
        $lastIndex = strlen(self::ALPHANUMERIC_CHARACTERS) - 1;

        for ($index = 0; $index < self::RANDOM_PART_LENGTH; ++$index) {
            $characters .= self::ALPHANUMERIC_CHARACTERS[random_int(0, $lastIndex)];
        }

        return $characters;
    }
}
