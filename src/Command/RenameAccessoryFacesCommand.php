<?php

namespace App\Command;

use App\Repository\Avatar\Faces\FacesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:avatar:rename-accessory-faces',
    description: 'Remplace le préfixe « accessory » des noms de visages par « visage ».',
)]
final class RenameAccessoryFacesCommand extends Command
{
    private const OLD_PREFIX = 'accessory';
    private const NEW_PREFIX = 'visage';

    public function __construct(
        private readonly FacesRepository $facesRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Affiche les changements sans les enregistrer.',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $faces = $this->facesRepository->findByNamePrefix(self::OLD_PREFIX);
        $dryRun = (bool) $input->getOption('dry-run');

        if ([] === $faces) {
            $io->success('Aucun visage dont le nom commence par « accessory » n’a été trouvé.');

            return Command::SUCCESS;
        }

        foreach ($faces as $face) {
            $oldName = (string) $face->getName();
            $newName = self::NEW_PREFIX . substr($oldName, strlen(self::OLD_PREFIX));
            $io->writeln(sprintf('%s → %s', $oldName, $newName));

            if (!$dryRun) {
                $face->setName($newName)->setEditedAt(new \DateTimeImmutable());
            }
        }

        if ($dryRun) {
            $io->note(sprintf('%d visage(s) seraient renommé(s). Aucun changement enregistré.', count($faces)));
        } else {
            $this->entityManager->flush();
            $io->success(sprintf('%d visage(s) renommé(s).', count($faces)));
        }

        return Command::SUCCESS;
    }
}
