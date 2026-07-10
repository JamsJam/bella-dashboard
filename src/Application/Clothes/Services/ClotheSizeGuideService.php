<?php

namespace App\Application\Clothes\Services;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\MeasurementType;
use App\Entity\SizeGuide;
use App\Entity\SizeGuideMeasurement;
use App\Entity\SizeGuideSize;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class ClotheSizeGuideService
{
    private const UNIT = 'cm';

    public const DEFAULT_MEASUREMENT_TYPES = [
        'sleeve_length' => 'Longueur de manche',
        'chest_width' => 'Poitrine',
        'shoulder_width' => 'Epaules',
        'body_length' => 'Longueur',
        'waist_width' => 'Taille',
        'hip_width' => 'Hanche',
        'inseam_length' => 'Entrejambe',
        'pants_length' => 'Longueur pantalon',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param list<ClothesVariant> $variants
     * @param array<string, array<string, mixed>> $measurements
     * @param list<string> $selectedTypeCodes
     */
    public function syncGuide(Clothes $mainClothe, array $variants, array $measurements, array $selectedTypeCodes): SizeGuide
    {
        $guide = $this->ensureGuideForVariants($mainClothe, $variants);
        $selectedTypes = $this->resolveMeasurementTypes($selectedTypeCodes);
        $selectedTypesByCode = $this->indexTypesByCode($selectedTypes);

        foreach ($guide->getSizes() as $size) {
            $this->removeUnselectedMeasurements($size, array_keys($selectedTypesByCode));
        }

        $position = 0;
        foreach ($measurements as $sizeLabel => $valuesByType) {
            $sizeLabel = trim((string) $sizeLabel);
            if ($sizeLabel === '') {
                continue;
            }

            $size = $this->findOrCreateSize($guide, $sizeLabel, $position++);
            $this->removeUnselectedMeasurements($size, array_keys($selectedTypesByCode));

            foreach ($valuesByType as $typeCode => $value) {
                $typeCode = $this->normalizeCode((string) $typeCode);

                if (!isset($selectedTypesByCode[$typeCode])) {
                    continue;
                }

                $value = str_replace(',', '.', trim((string) $value));
                if ($value === '' || !is_numeric($value) || (float) $value <= 0) {
                    continue;
                }

                $measurement = $this->findOrCreateMeasurement($size, $selectedTypesByCode[$typeCode]);
                $measurement
                    ->setValue(number_format((float) $value, 2, '.', ''))
                    ->setUnit(self::UNIT);
            }
        }

        $guide->setEditedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        return $guide;
    }

    /**
     * @param list<ClothesVariant> $variants
     * @param list<string> $selectedTypeCodes
     * @param array<string, array<string, mixed>> $submittedMeasurements
     * @return array{
     *     unit: string,
     *     availableTypes: list<array{code: string, label: string, selected: bool}>,
     *     types: list<array{code: string, label: string}>,
     *     rows: list<array{size: string, measurements: array<string, string|null>}>
     * }
     */
    public function buildPreviewView(Clothes $mainClothe, array $variants, array $selectedTypeCodes, array $submittedMeasurements): array
    {
        $allTypes = $this->getActiveMeasurementTypes();
        $selectedTypes = $this->resolveMeasurementTypes($selectedTypeCodes);
        $selectedCodes = array_values(array_filter(array_map(
            static fn (MeasurementType $type): ?string => $type->getCode(),
            $selectedTypes,
        )));
        $typeRows = $this->mapTypesForView($selectedTypes);
        $rows = $this->buildRowsFromSubmittedData($mainClothe, $variants, $typeRows, $submittedMeasurements);

        return [
            'unit' => $mainClothe->getSizeGuide()?->getUnit() ?? 'cm',
            'availableTypes' => $this->mapAvailableTypesForView($allTypes, $selectedCodes),
            'types' => $typeRows,
            'rows' => $rows,
        ];
    }

    /**
     * @param list<ClothesVariant> $variants
     * @return array{
     *     unit: string,
     *     availableTypes: list<array{code: string, label: string, selected: bool}>,
     *     types: list<array{code: string, label: string}>,
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

        if ($selectedTypes === []) {
            $selectedTypes = $allTypes;
        }

        $selectedCodes = array_values(array_filter(array_map(
            static fn (MeasurementType $type): ?string => $type->getCode(),
            $selectedTypes,
        )));

        $typeRows = $this->mapTypesForView($selectedTypes);
        $rows = $guide instanceof SizeGuide
            ? $this->buildRowsFromGuide($guide, $typeRows)
            : [];

        if ($rows === []) {
            $rows = $this->buildEmptyVariantRows($variants, $typeRows);
        }

        return [
            'unit' => self::UNIT,
            'availableTypes' => $this->mapAvailableTypesForView($allTypes, $selectedCodes),
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
        $types = $this->entityManager->getRepository(MeasurementType::class)->findBy(
            ['isActive' => true],
            ['position' => 'ASC', 'label' => 'ASC'],
        );

        if ($types !== []) {
            return $types;
        }

        $position = 0;
        $createdTypes = [];
        foreach (self::DEFAULT_MEASUREMENT_TYPES as $code => $label) {
            $createdTypes[] = $this->getOrCreateMeasurementType($code, $label, $position++);
        }

        return $createdTypes;
    }

    /**
     * @param list<string> $selectedTypeCodes
     * @return list<MeasurementType>
     */
    private function resolveMeasurementTypes(array $selectedTypeCodes): array
    {
        $selectedTypeCodes = array_values(array_unique(array_filter(array_map(
            fn (string $code): string => $this->normalizeCode($code),
            $selectedTypeCodes,
        ))));

        $types = [];
        foreach (self::DEFAULT_MEASUREMENT_TYPES as $code => $label) {
            if (in_array($code, $selectedTypeCodes, true)) {
                $types[] = $this->getOrCreateMeasurementType($code, $label);
            }
        }

        return $types;
    }

    private function getOrCreateMeasurementType(string $code, string $label, int $position = 0): MeasurementType
    {
        $code = $this->normalizeCode($code);
        $type = $this->entityManager->getRepository(MeasurementType::class)->findOneBy(['code' => $code]);

        if ($type instanceof MeasurementType) {
            return $type;
        }

        $type = (new MeasurementType())
            ->setCode($code)
            ->setLabel($label)
            ->setPosition($position)
            ->setIsActive(true);

        $this->entityManager->persist($type);

        return $type;
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
     * @param list<string> $selectedCodes
     */
    private function removeUnselectedMeasurements(SizeGuideSize $size, array $selectedCodes): void
    {
        foreach ($size->getMeasurements()->toArray() as $measurement) {
            if (!$measurement instanceof SizeGuideMeasurement) {
                continue;
            }

            $code = $measurement->getType()?->getCode();
            if ($code !== null && !in_array($code, $selectedCodes, true)) {
                $size->removeMeasurement($measurement);
            }
        }
    }

    /**
     * @return list<MeasurementType>
     */
    private function resolveTypesUsedByGuide(SizeGuide $guide): array
    {
        $typesByCode = [];

        foreach ($guide->getSizes() as $size) {
            foreach ($size->getMeasurements() as $measurement) {
                $type = $measurement->getType();
                if ($type instanceof MeasurementType && $type->getCode() !== null) {
                    $typesByCode[$type->getCode()] = $type;
                }
            }
        }

        $types = array_values($typesByCode);
        usort($types, static fn (MeasurementType $a, MeasurementType $b): int => ($a->getPosition() ?? 0) <=> ($b->getPosition() ?? 0));

        return $types;
    }

    /**
     * @param list<MeasurementType> $types
     * @return array<string, MeasurementType>
     */
    private function indexTypesByCode(array $types): array
    {
        $indexed = [];

        foreach ($types as $type) {
            if ($type->getCode() !== null) {
                $indexed[$type->getCode()] = $type;
            }
        }

        return $indexed;
    }

    /**
     * @param list<MeasurementType> $types
     * @return list<array{code: string, label: string}>
     */
    private function mapTypesForView(array $types): array
    {
        return array_map(
            static fn (MeasurementType $type): array => [
                'code' => (string) $type->getCode(),
                'label' => (string) $type->getLabel(),
            ],
            $types,
        );
    }

    /**
     * @param list<MeasurementType> $types
     * @param list<string> $selectedCodes
     * @return list<array{code: string, label: string, selected: bool}>
     */
    private function mapAvailableTypesForView(array $types, array $selectedCodes): array
    {
        return array_map(
            static fn (MeasurementType $type): array => [
                'code' => (string) $type->getCode(),
                'label' => (string) $type->getLabel(),
                'selected' => in_array((string) $type->getCode(), $selectedCodes, true),
            ],
            $types,
        );
    }

    /**
     * @param list<array{code: string, label: string}> $types
     * @return list<array{size: string, measurements: array<string, string|null>}>
     */
    private function buildRowsFromGuide(SizeGuide $guide, array $types): array
    {
        $rows = [];
        $typeCodes = array_column($types, 'code');

        foreach ($guide->getSizes() as $size) {
            $measurements = array_fill_keys($typeCodes, null);

            foreach ($size->getMeasurements() as $measurement) {
                $code = $measurement->getType()?->getCode();
                if ($code !== null && array_key_exists($code, $measurements)) {
                    $measurements[$code] = $measurement->getValue();
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
     * @param list<Clothes> $variants
     * @param list<array{code: string, label: string}> $types
     * @return list<array{size: string, measurements: array<string, string|null>}>
     */
    private function buildEmptyVariantRows(array $variants, array $types): array
    {
        $rows = [];
        $typeCodes = array_column($types, 'code');

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || $variant->getSize()?->getName() === null) {
                continue;
            }

            $rows[(string) $variant->getSize()->getName()] = [
                'size' => (string) $variant->getSize()->getName(),
                'measurements' => array_fill_keys($typeCodes, null),
            ];
        }

        return array_values($rows);
    }

    /**
     * @param list<Clothes> $variants
     * @param list<array{code: string, label: string}> $types
     * @param array<string, array<string, mixed>> $submittedMeasurements
     * @return list<array{size: string, measurements: array<string, string|null>}>
     */
    private function buildRowsFromSubmittedData(Clothes $mainClothe, array $variants, array $types, array $submittedMeasurements): array
    {
        $rows = [];
        $typeCodes = array_column($types, 'code');
        $existingRows = [];

        $guide = $mainClothe->getSizeGuide();
        if ($guide instanceof SizeGuide) {
            foreach ($this->buildRowsFromGuide($guide, $types) as $row) {
                $existingRows[$row['size']] = $row['measurements'];
            }
        }

        foreach ($variants as $variant) {
            if (!$variant instanceof ClothesVariant || $variant->getSize()?->getName() === null) {
                continue;
            }

            $sizeLabel = (string) $variant->getSize()->getName();
            $measurements = array_fill_keys($typeCodes, null);

            foreach ($typeCodes as $typeCode) {
                $submittedValue = $submittedMeasurements[$sizeLabel][$typeCode] ?? null;
                $measurements[$typeCode] = $submittedValue !== null && $submittedValue !== ''
                    ? (string) $submittedValue
                    : ($existingRows[$sizeLabel][$typeCode] ?? null);
            }

            $rows[$sizeLabel] = [
                'size' => $sizeLabel,
                'measurements' => $measurements,
            ];
        }

        return array_values($rows);
    }

    private function normalizeCode(string $code): string
    {
        return strtolower((string) (new AsciiSlugger())->slug(trim($code), '_'));
    }

}
