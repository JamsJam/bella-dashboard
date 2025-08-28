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
     * @var Collection<int, Clothes>
     */
    #[ORM\OneToMany(targetEntity: Clothes::class, mappedBy: 'color', cascade: ['persist'])]
    private Collection $clothes;

    public function __construct()
    {
        $this->clothes = new ArrayCollection();
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
            $clothes->setColor($this);
        }

        return $this;
    }

    public function removeClothes(Clothes $clothes): static
    {
        if ($this->clothes->removeElement($clothes)) {
            // set the owning side to null (unless already changed)
            if ($clothes->getColor() === $this) {
                $clothes->setColor(null);
            }
        }

        return $this;
    }
}
