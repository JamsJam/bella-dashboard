<?php

namespace App\Entity\Clothes;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DateFieldsTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Clothes\ClothessizeRepository;

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
     * @var Collection<int, Clothes>
     */
    #[ORM\OneToMany(targetEntity: Clothes::class, mappedBy: 'size')]
    private Collection $clothes;

    public function __construct()
    {
        $this->clothes = new ArrayCollection();
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
        return $this->clothes;
    }

    public function addClothes(Clothes $clothes): static
    {
        if (!$this->clothes->contains($clothes)) {
            $this->clothes->add($clothes);
            $clothes->setSize($this);
        }

        return $this;
    }

    public function removeClothes(Clothes $clothes): static
    {
        if ($this->clothes->removeElement($clothes)) {
            // set the owning side to null (unless already changed)
            if ($clothes->getSize() === $this) {
                $clothes->setSize(null);
            }
        }

        return $this;
    }
}
