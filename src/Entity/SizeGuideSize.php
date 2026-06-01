<?php

namespace App\Entity;

use App\Repository\SizeGuideSizeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SizeGuideSizeRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_SIZE_GUIDE_SIZE_LABEL', columns: ['size_guide_id', 'label'])]
class SizeGuideSize
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private ?string $label = null;

    #[ORM\Column]
    private ?int $position = null;

    /**
     * @var Collection<int, SizeGuideMeasurement>
     */
    #[ORM\OneToMany(targetEntity: SizeGuideMeasurement::class, mappedBy: 'sizeGuideSize', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $measurements;

    #[ORM\ManyToOne(inversedBy: 'sizes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SizeGuide $sizeGuide = null;

    public function __construct()
    {
        $this->measurements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $measurement->setSizeGuideSize($this);
        }

        return $this;
    }

    public function removeMeasurement(SizeGuideMeasurement $measurement): static
    {
        if ($this->measurements->removeElement($measurement)) {
            // set the owning side to null (unless already changed)
            if ($measurement->getSizeGuideSize() === $this) {
                $measurement->setSizeGuideSize(null);
            }
        }

        return $this;
    }

    public function getSizeGuide(): ?SizeGuide
    {
        return $this->sizeGuide;
    }

    public function setSizeGuide(?SizeGuide $sizeGuide): static
    {
        $this->sizeGuide = $sizeGuide;

        return $this;
    }
}
