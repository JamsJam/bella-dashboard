<?php

namespace App\Command;

use App\Entity\Users\Admin;
use App\Repository\Users\AdminRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'create-admin',
    description: 'Cree un administrateur autorise a se connecter au dashboard.',
)]
final class CreateAdminCommand extends Command
{
    public function __construct(
        private readonly AdminRepository $adminRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creation d un administrateur');

        $email = (string) $io->ask('Adresse e-mail', null, $this->validateEmail(...));
        $password = (string) $io->askHidden('Mot de passe', $this->validatePassword(...));
        $passwordConfirmation = (string) $io->askHidden('Confirmez le mot de passe', $this->validatePassword(...));

        if ($password !== $passwordConfirmation) {
            $io->error('Les mots de passe ne correspondent pas.');

            return Command::FAILURE;
        }

        $now = new \DateTimeImmutable();
        $admin = (new Admin())
            ->setEmail($email)
            ->setRoles(['ROLE_ADMIN'])
            ->setCreatedAt($now)
            ->setEditedAt($now);

        $admin->setPassword($this->passwordHasher->hashPassword($admin, $password));

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur %s cree avec succes.', $email));

        return Command::SUCCESS;
    }

    private function validateEmail(?string $email): string
    {
        $email = strtolower(trim((string) $email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Saisissez une adresse e-mail valide.');
        }

        if ($this->adminRepository->findOneBy(['email' => $email]) instanceof Admin) {
            throw new \InvalidArgumentException('Un administrateur utilise deja cette adresse e-mail.');
        }

        return $email;
    }

    private function validatePassword(?string $password): string
    {
        $password = (string) $password;

        if (mb_strlen($password) < 12) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 12 caracteres.');
        }

        return $password;
    }
}
