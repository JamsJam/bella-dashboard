<?php

namespace App\Entity\Avatar\Eyes;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Eyes\EyeshapeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EyeshapeRepository::class)]
class Eyeshape
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, Eye>
     */
    #[ORM\OneToMany(targetEntity: Eye::class, mappedBy: 'shape', orphanRemoval: true)]
    private Collection $eyes;

    public function __construct()
    {
        $this->eyes = new ArrayCollection();
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
     * @return Collection<int, Eye>
     */
    public function getEyes(): Collection
    {
        return $this->eyes;
    }

    public function addEye(Eye $eye): static
    {
        if (!$this->eyes->contains($eye)) {
            $this->eyes->add($eye);
            $eye->setShape($this);
        }

        return $this;
    }

    public function removeEye(Eye $eye): static
    {
        if ($this->eyes->removeElement($eye)) {
            // set the owning side to null (unless already changed)
            if ($eye->getShape() === $this) {
                $eye->setShape(null);
            }
        }

        return $this;
    }
}
