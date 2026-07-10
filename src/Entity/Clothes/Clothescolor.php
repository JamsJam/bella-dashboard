<?php

namespace App\Entity\Clothes;

use App\Entity\Traits\ColorFieldsTrait;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Clothes\ClothescolorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClothescolorRepository::class)]
class Clothescolor
{
    use DateFieldsTrait;
    use ColorFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    
    /**
     * @var Collection<int, ClothesVariant>
     */
    #[ORM\OneToMany(targetEntity: ClothesVariant::class, mappedBy: 'color', cascade: ['persist'])]
    private Collection $variants;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
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
            if ($clothe instanceof Clothes && $clothe->getId() !== null) {
                $clothes[$clothe->getId()] = $clothe;
            }
        }

        return new ArrayCollection(array_values($clothes));
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
            $variant->setColor($this);
        }

        return $this;
    }

    public function removeVariant(ClothesVariant $variant): static
    {
        if ($this->variants->removeElement($variant) && $variant->getColor() === $this) {
            $variant->setColor(null);
        }

        return $this;
    }
}
