<?php

namespace App\Entity\Clothes;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Clothes\ClothessizeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClothessizeRepository::class)]
class Clothessize
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 5)]
    private ?string $name = null;

    /**
     * @var Collection<int, ClothesVariant>
     */
    #[ORM\OneToMany(targetEntity: ClothesVariant::class, mappedBy: 'size')]
    private Collection $variants;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Clothes>
     */
    public function getClothes(): Collection
    {
        $clothes = [];

        foreach ($this->variants as $variant) {
            $clothe = $variant->getClothes();
            if ($clothe instanceof Clothes && null !== $clothe->getId()) {
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
            $variant->setSize($this);
        }

        return $this;
    }

    public function removeVariant(ClothesVariant $variant): static
    {
        if ($this->variants->removeElement($variant) && $variant->getSize() === $this) {
            $variant->setSize(null);
        }

        return $this;
    }
}
