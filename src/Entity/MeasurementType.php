<?php

namespace App\Entity;

use App\Repository\MeasurementTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MeasurementTypeRepository::class)]
class MeasurementType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $uuid;

    #[ORM\Column(length: 120)]
    private ?string $label = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $position = null;

    /**
     * @var Collection<int, SizeGuideMeasurement>
     */
    #[ORM\OneToMany(targetEntity: SizeGuideMeasurement::class, mappedBy: 'type', cascade: ['remove'])]
    private Collection $measurements;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
        $this->measurements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return Collection<int, SizeGuideMeasurement>
     */
    public function getMeasurements(): Collection
    {
        return $this->measurements;
    }

    public function addMeasurement(SizeGuideMeasurement $measurement): static
    {
        if (!$this->measurements->contains($measurement)) {
            $this->measurements->add($measurement);
            $measurement->setType($this);
        }

        return $this;
    }

    public function removeMeasurement(SizeGuideMeasurement $measurement): static
    {
        if ($this->measurements->removeElement($measurement)) {
            if ($measurement->getType() === $this) {
                $measurement->setType(null);
            }
        }

        return $this;
    }
}
