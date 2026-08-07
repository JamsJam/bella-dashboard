<?php

namespace App\Application\Config\Service;

use App\Application\Config\Dto\ClothesConfigDto;
use App\Application\Config\Dto\SizeGuideItemDto;
use App\Application\Config\Provider\ClothesConfigProvider;
use App\Entity\MeasurementType;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ClothesConfigService
{
    public function __construct(
        private ClothesConfigProvider $provider,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function get(): ClothesConfigDto
    {
        $config = $this->provider->get();
        $types = $this->entityManager->getRepository(MeasurementType::class)->findBy([], ['position' => 'ASC']);
        $config->sizeGuideItems = array_map(
            static fn (MeasurementType $type): SizeGuideItemDto => new SizeGuideItemDto(
                uuid: $type->getUuid()->toRfc4122(),
                label: (string) $type->getLabel(),
                description: $type->getDescription(),
                measurementCount: $type->getMeasurements()->count(),
            ),
            $types,
        );

        return $config;
    }

    public function save(ClothesConfigDto $config): void
    {
        $this->syncMeasurementTypes($config);
        $this->provider->save($config);
    }

    private function syncMeasurementTypes(ClothesConfigDto $config): void
    {
        $repository = $this->entityManager->getRepository(MeasurementType::class);
        $existingTypes = $repository->findAll();
        $existingByUuid = [];

        foreach ($existingTypes as $type) {
            if ($type instanceof MeasurementType) {
                $existingByUuid[$type->getUuid()->toRfc4122()] = $type;
            }
        }

        foreach ($config->sizeGuideItems as $position => $item) {
            $type = null !== $item->uuid ? ($existingByUuid[$item->uuid] ?? null) : null;
            $type ??= new MeasurementType();
            $type
                ->setLabel(trim($item->label))
                ->setDescription($item->description)
                ->setPosition($position);

            if (null === $type->getId()) {
                $this->entityManager->persist($type);
            }

            unset($existingByUuid[$type->getUuid()->toRfc4122()]);
        }

        foreach ($existingByUuid as $removedType) {
            $this->entityManager->remove($removedType);
        }

        $this->entityManager->flush();
    }
}
