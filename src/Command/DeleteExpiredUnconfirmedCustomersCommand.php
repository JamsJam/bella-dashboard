<?php

namespace App\Command;

use App\Application\Auth\ExpiredUnconfirmedCustomersPurger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:customers:delete-expired-unconfirmed',
    description: 'Supprime les comptes customer non confirmes dont le code d inscription a expire.',
)]
final class DeleteExpiredUnconfirmedCustomersCommand extends Command
{
    public function __construct(
        private readonly ExpiredUnconfirmedCustomersPurger $expiredUnconfirmedCustomersPurger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $deletedCount = $this->expiredUnconfirmedCustomersPurger->purge();

        $io->success(sprintf('%d compte(s) customer non confirme(s) supprime(s).', (int) $deletedCount));

        return Command::SUCCESS;
    }
}
