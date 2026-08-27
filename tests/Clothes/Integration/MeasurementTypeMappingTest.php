<?php

namespace App\Tests\Clothes\Integration;

use App\Entity\MeasurementType;
use App\Entity\SizeGuideMeasurement;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/** Vérifie le mapping réel utilisé pour identifier et supprimer les types de mesure. */
#[Group('clothes')]
#[Group('integration')]
final class MeasurementTypeMappingTest extends KernelTestCase
{
    public function testUuidAndMeasurementDeletionCascadeAreMapped(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $typeMetadata = $entityManager->getClassMetadata(MeasurementType::class);
        $measurementMetadata = $entityManager->getClassMetadata(SizeGuideMeasurement::class);

        self::assertSame('uuid', $typeMetadata->getTypeOfField('uuid'), 'Blocage : l’UUID du type n’est pas mappé.');
        self::assertTrue(
            $typeMetadata->isUniqueField('uuid'),
            'Blocage : deux types de mesure pourraient partager le même UUID.',
        );
        self::assertContains(
            'remove',
            $typeMetadata->associationMappings['measurements']->cascade,
            'Blocage : Doctrine ne supprimera pas les mesures rattachées au type.',
        );
        self::assertSame(
            'CASCADE',
            $measurementMetadata->associationMappings['type']->joinColumns[0]->onDelete,
            'Blocage : la base ne supprimera pas les mesures associées au type.',
        );
        self::assertSame(
            'string',
            $measurementMetadata->getTypeOfField('value'),
            'Blocage : une mesure libre ou un intervalle ne pourrait pas être enregistré.',
        );
        self::assertSame(100, $measurementMetadata->fieldMappings['value']->length);
    }
}
