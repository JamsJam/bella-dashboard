<?php

namespace App\Application\Clothes\Services\Clothe;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\MeasurementType;
use App\Entity\SizeGuide;
use App\Entity\SizeGuideMeasurement;
use App\Entity\SizeGuideSize;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class ClotheSizeGuideService
{
    private const UNIT = 'cm';

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<ClothesVariant>                $variants
     * @param array<string, array<string, mixed>> $measurements
     * @param list<string>                        $selectedTypeUuids
     */
    public function syncGuide(Clothes $mainClothe, array $variants, array $measurements, array $selectedTypeUuids): SizeGuide
    {
        $guide = $this->ensureGuideForVariants($mainClothe, $variants);
        $selectedTypes = $this->resolveMeasurementTypes($selectedTypeUuids);
        $selectedTypesByUuid = $this->indexTypesByUuid($selectedTypes);

        foreach ($guide->getSizes() as $size) {
            $this->removeUnselectedMeasurements($size, array_keys($selectedTypesByUuid));
        }

        $position = 0;
        foreach ($measurements as $sizeLabel => $valuesByType) {
            $sizeLabel = trim((string) $sizeLabel);
            if ('' === $sizeLabel) {
                continue;
            }

            $size = $this->findOrCreateSize($guide, $sizeLabel, $position++);
            $this->removeUnselectedMeasurements($size, array_keys($selectedTypesByUuid));

            foreach ($valuesByType as $typeUuid => $value) {
                $typeUuid = (string) $typeUuid;

                if (!isset($selectedTypesByUuid[$typeUuid])) {
                    continue;
                }

                $value = trim((string) $value);
                if ('' === $value) {
                    continue;
                }

                $measurement = $this->findOrCreateMeasurement($size, $selectedTypesByUuid[$typeUuid]);
                $measurement
                    ->setValue($value)
                    ->setUnit(self::UNIT);
            }
        }

        $guide->setEditedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $guide;
    }

    /**
     * @param list<ClothesVariant>                $variants
     * @param list<string>                        $selectedTypeUuids
     * @param array<string, array<string, mixed>> $submittedMeasurements
     *
     * @return array{
     *     unit: string,
     *     availableTypes: list<array{uuid: string, label: string, selected: bool}>,
     *     types: list<array{uuid: string, label: string}>,
     *     rows: list<array{size: string, measurements: array<string, string|null>}>
     * }
     */
    public function buildPreviewView(Clothes $mainClothe, array $variants, array $selectedTypeUuids, array $submittedMeasurements): array
    {
        $allTypes = $this->getActiveMeasurementTypes();
        $selectedTypes = $this->resolveMeasurementTypes($selectedTypeUuids);
        $selectedUuids = array_map(
            static fn (MeasurementType $type): string => $type->getUuid()->toRfc4122(),
            $selectedTypes,
        );
        $typeRows = $this->mapTypesForView($selectedTypes);
        $rows = $this->buildRowsFromSubmittedData($mainClothe, $variants, $typeRows, $submittedMeasurements);

        return [
            'unit' => $mainClothe->getSizeGuide()?->getUnit() ?? 'cm',
            'availableTypes' => $this->mapAvailableTypesForView($allTypes, $selectedUuids),
            'types' => $typeRows,
            'rows' => $rows,
        ];
    }

    /**
     * @param list<ClothesVariant> $variants
     *
     * @return array{
     *     unit: string,
     *     availableTypes: list<array{uuid: string, label: string, selected: bool}>,
     *     types: list<array{uuid: string, label: string}>,
     *     rows: list<array{size: string, measurements: array<string, string|null>}>
     * }
     */
    public function buildView(Clothes $mainClothe, array $variants): array
    {
        $guide = $mainClothe->getSizeGuide();
        $allTypes = $this->getActiveMeasurementTypes();
        $selectedTypes = $guide instanceof SizeGuide
            ? $this->resolveTypesUsedByGuide($guide)
            : $allTypes;

        if ([] === $selectedTypes) {
            $selectedTypes = $allTypes;
        }

        $selectedUuids = array_map(
            static fn (MeasurementType $type): string => $type->getUuid()->toRfc4122(),
            $selectedTypes,
        );

        $typeRows = $this->mapTypesForView($selectedTypes);
        $rows = $guide instanceof SizeGuide
            ? $this->buildRowsFromGuide($guide, $typeRows)
            : [];

        if ([] === $rows) {
            $rows = $this->buildEmptyVariantRows($variants, $typeRows);
        }

        return [
            'unit' => self::UNIT,
            'availableTypes' => $this->mapAvailableTypesForView($allTypes, $selectedUuids),
            'types' => $typeRows,
            'rows' => $rows,
        ];
    }

    /**
     * @param list<ClothesVariant> $variants
     */
    private function ensureGuideForVariants(Clothes $mainClothe, array $variants): SizeGuide
    {
        $guide = $mainClothe->getSizeGuide();

        if (!$guide instanceof SizeGuide) {
            $now = new \DateTimeImmutable();
            $guide = (new SizeGuide())
                ->setUnit(self::UNIT)
                ->setCreatedAt($now)
                ->setEditedAt($now);

            $this->entityManager->persist($guide);
        } else {
            $guide->setUnit(self::UNIT);
        }

        foreach ($variants as $variant) {
            $mainClothe->setSizeGuide($guide);
        }

        return $guide;
    }

    /**
     * @return list<MeasurementType>
     */
    private function getActiveMeasurementTypes(): array
    {
        return $this->entityManager->getRepository(MeasurementType::class)->findBy(
            [],
            ['position' => 'ASC', 'label' => 'ASC'],
        );
    }

    /**
     * @param list<string> $selectedTypeUuids
     *
     * @return list<MeasurementType>
     */
    private function resolveMeasurementTypes(array $selectedTypeUuids): array
    {
        $types = [];
        foreach (array_unique($selectedTypeUuids) as $uuid) {
            if (!is_string($uuid) || !Uuid::isValid($uuid)) {
                continue;
            }

            $type = $this->entityManager->getRepository(MeasurementType::class)->findOneBy(['uuid' => $uuid]);

            if ($type instanceof MeasurementType) {
                $types[] = $type;
            }
        }

        return $types;
    }

    private function findOrCreateSize(SizeGuide $guide, string $label, int $position): SizeGuideSize
    {
        foreach ($guide->getSizes() as $size) {
            if (strtolower((string) $size->getLabel()) === strtolower($label)) {
                return $size->setPosition($position);
            }
        }

        $size = (new SizeGuideSize())
            ->setLabel($label)
            ->setPosition($position);

        $guide->addSize($size);

        return $size;
    }

    private function findOrCreateMeasurement(SizeGuideSize $size, MeasurementType $type): SizeGuideMeasurement
    {
        foreach ($size->getMeasurements() as $measurement) {
            if ($measurement->getType() === $type) {
                return $measurement;
            }
        }

        $measurement = (new SizeGuideMeasurement())->setType($type);
        $size->addMeasurement($measurement);

        return $measurement;
    }

    /**
     * @param list<string> $selectedUuids
     */
    private function removeUnselectedMeasurements(SizeGuideSize $size, array $selectedUuids): void
    {
        foreach ($size->getMeasurements()->toArray() as $measurement) {
            if (!$measurement instanceof SizeGuideMeasurement) {
                continue;
            }

            $uuid = $measurement->getType()?->getUuid()->toRfc4122();
            if (null !== $uuid && !in_array($uuid, $selectedUuids, true)) {
                $size->removeMeasurement($measurement);
            }
        }
    }

    /**
     * @return list<MeasurementType>
     */
    private function resolveTypesUsedByGuide(SizeGuide $guide): array
    {
        $typesByUuid = [];

        foreach ($guide->getSizes() as $size) {
            foreach ($size->getMeasurements() as $measurement) {
                $type = $measurement->getType();
                if ($type instanceof MeasurementType) {
                    $typesByUuid[$type->getUuid()->toRfc4122()] = $type;
                }
            }
        }

        $types = array_values($typesByUuid);
        usort($types, static fn (MeasurementType $a, MeasurementType $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0));

        return $types;
    }

    /**
     * @param list<MeasurementType> $types
     *
     * @return array<string, MeasurementType>
     */
    private function indexTypesByUuid(array $types): array
    {
        $indexed = [];

        foreach ($types as $type) {
            $indexed[$type->getUuid()->toRfc4122()] = $type;
        }

        return $indexed;
    }

    /**
     * @param list<MeasurementType> $types
     *
     * @return list<array{uuid: string, label: string}>
     */
    private function mapTypesForView(array $types): array
    {
        return array_map(
            static fn (MeasurementType $type): array => [
                'uuid' => $type->getUuid()->toRfc4122(),
                'label' => (string) $type->getLabel(),
            ],
            $types,
        );
    }

    /**
     * @param list<MeasurementType> $types
     * @param list<string>          $selectedUuids
     *
     * @return list<array{uuid: string, label: string, selected: bool}>
     */
    private function mapAvailableTypesForView(array $types, array $selectedUuids): array
    {
        return array_map(
            static fn (MeasurementType $type): array => [
                'uuid' => $type->getUuid()->toRfc4122(),
                'label' => (string) $type->getLabel(),
                'selected' => in_array($type->getUuid()->toRfc4122(), $selectedUuids, true),
            ],
            $types,
        );
    }

    /**
     * @param list<array{uuid: string, label: string}> $types
     *
     * @return list<array{size: string, measurements: array<string, string|null>}>
     */
    private function buildRowsFromGuide(SizeGuide $guide, array $types): array
    {
        $rows = [];
        $typeUuids = array_column($types, 'uuid');

        foreach ($guide->getSizes() as $size) {
            $measurements = array_fill_keys($typeUuids, null);

            foreach ($size->getMeasurements() as $measurement) {
                $uuid = $measurement->getType()?->getUuid()->toRfc4122();
                if (null !== $uuid && array_key_exists($uuid, $measurements)) {
                    $measurements[$uuid] = $measurement->getValue();
                }
            }

            $rows[] = [
                'size' => (string) $size->getLabel(),
                'measurements' => $measurements,
            ];
        }

        return $rows;
    }

    /**
     * @param list<Clothes>                            $variants
     * @param list<array{uuid: string, label: string}> $types
     *
     * @return list<array{size: string, measurements: array<string, string|null>}>
     */
    private function buildEmptyVariantRows(array $variants, array $types): array
    {
        $rows = [];
        $typeUuids = array_column($types, 'uuid');

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || null === $variant->getSize()?->getName()) {
                continue;
            }

            $rows[(string) $variant->getSize()->getName()] = [
                'size' => (string) $variant->getSize()->getName(),
                'measurements' => array_fill_keys($typeUuids, null),
            ];
        }

        return array_values($rows);
    }

    /**
     * @param list<Clothes>                            $variants
     * @param list<array{uuid: string, label: string}> $types
     * @param array<string, array<string, mixed>>      $submittedMeasurements
     *
     * @return list<array{size: string, measurements: array<string, string|null>}>
     */
    private function buildRowsFromSubmittedData(Clothes $mainClothe, array $variants, array $types, array $submittedMeasurements): array
    {
        $rows = [];
        $typeUuids = array_column($types, 'uuid');
        $existingRows = [];

        $guide = $mainClothe->getSizeGuide();
        if ($guide instanceof SizeGuide) {
            foreach ($this->buildRowsFromGuide($guide, $types) as $row) {
                $existingRows[$row['size']] = $row['measurements'];
            }
        }

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || null === $variant->getSize()?->getName()) {
                continue;
            }

            $sizeLabel = (string) $variant->getSize()->getName();
            $measurements = array_fill_keys($typeUuids, null);

            foreach ($typeUuids as $typeUuid) {
                $submittedValue = $submittedMeasurements[$sizeLabel][$typeUuid] ?? null;
                $measurements[$typeUuid] = null !== $submittedValue && '' !== $submittedValue
                    ? (string) $submittedValue
                    : ($existingRows[$sizeLabel][$typeUuid] ?? null);
            }

            $rows[$sizeLabel] = [
                'size' => $sizeLabel,
                'measurements' => $measurements,
            ];
        }

        return array_values($rows);
    }
}
