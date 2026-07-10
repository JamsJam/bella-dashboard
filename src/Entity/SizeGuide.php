<?php

namespace App\Entity;

use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
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
     * @var Collection<int, ClothesVariant>
     */
    #[ORM\OneToMany(targetEntity: ClothesVariant::class, mappedBy: 'sizeGuide')]
    private Collection $variants;

    #[ORM\Column(length: 8)]
    private ?string $unit = null;

    /**
     * @var Collection<int, SizeGuideSize>
     */
    #[ORM\OneToMany(targetEntity: SizeGuideSize::class, mappedBy: 'sizeGuide', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $sizes;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
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
        $clothes = [];

        foreach ($this->variants as $variant) {
            $clothe = $variant->getClothes();
            if (!$clothe instanceof Clothes) {
                continue;
            }

            if ($clothe->getId() !== null) {
                $clothes[$clothe->getId()] = $clothe;
                continue;
            }

            $clothes[] = $clothe;
        }

        return new ArrayCollection(array_values($clothes));
    }

    public function addClothes(Clothes $clothes): static
    {
        foreach ($clothes->getVariants() as $variant) {
            $this->addVariant($variant);
        }

        return $this;
    }

    public function removeClothes(Clothes $clothes): static
    {
        foreach ($clothes->getVariants() as $variant) {
            $this->removeVariant($variant);
        }

        return $this;
    }

    /**
     * @return Collection<int, ClothesVariant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(ClothesVariant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setSizeGuide($this);
        }

        return $this;
    }

    public function removeVariant(ClothesVariant $variant): static
    {
        if ($this->variants->removeElement($variant) && $variant->getSizeGuide() === $this) {
            $variant->setSizeGuide(null);
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
