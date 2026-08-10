<?php

namespace App\Command;

use App\Application\Clothes\Services\Clothe\ClotheWorkflowService;
use App\Entity\Clothes\ClothesVariant;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:clothes:reconcile-publication-status',
    description: 'Vérifie toutes les variantes et réconcilie leur statut avec leur complétude.',
)]
final class ReconcileClothesVariantPublicationStatusCommand extends Command
{
    public function __construct(
        private readonly ClothesVariantRepository $repository,
        private readonly ClotheWorkflowService $workflowService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $checked = 0;
        $updated = 0;

        foreach ($this->repository->findAll() as $variant) {
            if (!$variant instanceof ClothesVariant) {
                continue;
            }

            ++$checked;
            if ($this->workflowService->reconcileVariant($variant)) {
                ++$updated;
                $output->writeln(sprintf(
                    '<info>#%d %s → %s</info>',
                    $variant->getId(),
                    $variant->getName(),
                    $variant->getPublicationStatus()->label(),
                ));
            }
        }

        $output->writeln(sprintf(
            '%d variante(s) vérifiée(s), %d statut(s) mis à jour.',
            $checked,
            $updated,
        ));

        return Command::SUCCESS;
    }
}
