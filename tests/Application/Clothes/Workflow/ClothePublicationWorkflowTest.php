<?php

namespace App\Tests\Application\Clothes\Workflow;

use App\Entity\Clothes\ClothesVariant;
use App\Enum\ClotheStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

final class ClothePublicationWorkflowTest extends KernelTestCase
{
    public function testAllRequestedTransitionsAreConfigured(): void
    {
        self::bootKernel();
        /** @var WorkflowInterface $workflow */
        $workflow = self::getContainer()->get('state_machine.clothe_publication');

        $expected = [
            ClotheStatus::Draft->value => ['rendre_publiable', 'archiver_brouillon'],
            ClotheStatus::Publishable->value => ['repasser_en_brouillon', 'programmer_publication', 'publier', 'archiver_publiable'],
            ClotheStatus::Scheduled->value => ['annuler_programmation', 'invalider_programmation', 'publier_automatiquement', 'archiver_planifie'],
            ClotheStatus::Online->value => ['depublier', 'archiver_en_ligne'],
            ClotheStatus::Offline->value => ['remettre_en_ligne', 'modifier_hors_ligne', 'archiver_hors_ligne'],
            ClotheStatus::Archived->value => ['restaurer'],
        ];

        foreach ($expected as $status => $transitions) {
            $variant = (new ClothesVariant())->setPublicationStatus(ClotheStatus::from($status));
            self::assertSame($transitions, array_map(
                static fn ($transition): string => $transition->getName(),
                $workflow->getEnabledTransitions($variant),
            ));
        }
    }
}
