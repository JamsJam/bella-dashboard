<?php

namespace App\Entity;

use App\Entity\Clothes\Clothes;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\SizeGuideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SizeGuideRepository::class)]
class SizeGuide
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Clothes>
     */
    #[ORM\OneToMany(targetEntity: Clothes::class, mappedBy: 'sizeGuide')]
    private Collection $clothes;

    #[ORM\Column(length: 8)]
    private ?string $unit = null;

    /**
     * @var Collection<int, SizeGuideSize>
     */
    #[ORM\OneToMany(targetEntity: SizeGuideSize::class, mappedBy: 'sizeGuide', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sizes;

    public function __construct()
    {
        $this->clothes = new ArrayCollection();
        $this->sizes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Clothes>
     */
    public function getClothes(): Collection
    {
        return $this->clothes;
    }

    public function addClothes(Clothes $clothes): static
    {
        if (!$this->clothes->contains($clothes)) {
            $this->clothes->add($clothes);
            $clothes->setSizeGuide($this);
        }

        return $this;
    }

    public function removeClothes(Clothes $clothes): static
    {
        if ($this->clothes->removeElement($clothes) && $clothes->getSizeGuide() === $this) {
            $clothes->setSizeGuide(null);
        }

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

    /**
     * @return Collection<int, SizeGuideSize>
     */
    public function getSizes(): Collection
    {
        return $this->sizes;
    }

    public function addSize(SizeGuideSize $size): static
    {
        if (!$this->sizes->contains($size)) {
            $this->sizes->add($size);
            $size->setSizeGuide($this);
        }

        return $this;
    }

    public function removeSize(SizeGuideSize $size): static
    {
        if ($this->sizes->removeElement($size)) {
            // set the owning side to null (unless already changed)
            if ($size->getSizeGuide() === $this) {
                $size->setSizeGuide(null);
            }
        }

        return $this;
    }
}
