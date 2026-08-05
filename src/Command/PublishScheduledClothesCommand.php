<?php

namespace App\Command;

use App\Application\Clothes\Services\ClotheCompletenessChecker;
use App\Application\Clothes\Services\ClotheWorkflowService;
use App\Repository\Clothes\ClothesVariantRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:clothes:publish-scheduled', description: 'Publie les vêtements dont la date programmée est atteinte.')]
final class PublishScheduledClothesCommand extends Command
{
    public function __construct(
        private readonly ClothesVariantRepository $repository,
        private readonly ClotheCompletenessChecker $completenessChecker,
        private readonly ClotheWorkflowService $workflow,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $published = 0;
        $invalidated = 0;

        foreach ($this->repository->findScheduledForPublication(new \DateTimeImmutable()) as $variant) {
            $transition = $this->completenessChecker->checkVariant($variant)->isComplete()
                ? 'publier_automatiquement'
                : 'invalider_programmation';
            $this->workflow->apply($variant, $transition);
            $transition === 'publier_automatiquement' ? ++$published : ++$invalidated;
        }

        $output->writeln(sprintf('%d vêtement(s) publié(s), %d programmation(s) invalidée(s).', $published, $invalidated));

        return Command::SUCCESS;
    }
}
