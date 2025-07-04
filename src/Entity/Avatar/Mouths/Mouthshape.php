<?php

namespace App\Entity\Avatar\Mouths;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DateFieldsTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Avatar\Mouths\MouthshapeRepository;

#[ORM\Entity(repositoryClass: MouthshapeRepository::class)]
class Mouthshape
{
    use DateFieldsTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, Mouths>
     */
    #[ORM\OneToMany(targetEntity: Mouths::class, mappedBy: 'shape', orphanRemoval: true)]
    private Collection $mouths;

    public function __construct()
    {
        $this->mouths = new ArrayCollection();
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
     * @return Collection<int, Mouths>
     */
    public function getMouths(): Collection
    {
        return $this->mouths;
    }

    public function addMouth(Mouths $mouth): static
    {
        if (!$this->mouths->contains($mouth)) {
            $this->mouths->add($mouth);
            $mouth->setShape($this);
        }

        return $this;
    }

    public function removeMouth(Mouths $mouth): static
    {
        if ($this->mouths->removeElement($mouth)) {
            // set the owning side to null (unless already changed)
            if ($mouth->getShape() === $this) {
                $mouth->setShape(null);
            }
        }

        return $this;
    }
}
