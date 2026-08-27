<?php

namespace App\Entity;

use App\Repository\SizeGuideMeasurementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SizeGuideMeasurementRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SIZE_GUIDE_MEASUREMENT_TYPE', columns: ['size_guide_size_id', 'type_id'])]
class SizeGuideMeasurement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $value = null;

    #[ORM\Column(length: 8)]
    private ?string $unit = null;

    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MeasurementType $type = null;

    #[ORM\ManyToOne(inversedBy: 'measurements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SizeGuideSize $sizeGuideSize = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getType(): ?MeasurementType
    {
        return $this->type;
    }

    public function setType(?MeasurementType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSizeGuideSize(): ?SizeGuideSize
    {
        return $this->sizeGuideSize;
    }

    public function setSizeGuideSize(?SizeGuideSize $sizeGuideSize): static
    {
        $this->sizeGuideSize = $sizeGuideSize;

        return $this;
    }
}
