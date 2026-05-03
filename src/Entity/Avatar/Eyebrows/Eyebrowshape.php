<?php

namespace App\Entity\Avatar\Eyebrows;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Eyebrows\EyebrowshapeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EyebrowshapeRepository::class)]
class Eyebrowshape
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, Eyebrows>
     */
    #[ORM\OneToMany(targetEntity: Eyebrows::class, mappedBy: 'shape', orphanRemoval: true)]
    private Collection $eyebrows;

    public function __construct()
    {
        $this->eyebrows = new ArrayCollection();
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
     * @return Collection<int, Eyebrows>
     */
    public function getEyebrows(): Collection
    {
        return $this->eyebrows;
    }

    public function addEyebrow(Eyebrows $eyebrow): static
    {
        if (!$this->eyebrows->contains($eyebrow)) {
            $this->eyebrows->add($eyebrow);
            $eyebrow->setShape($this);
        }

        return $this;
    }

    public function removeEyebrow(Eyebrows $eyebrow): static
    {
        if ($this->eyebrows->removeElement($eyebrow)) {
            // set the owning side to null (unless already changed)
            if ($eyebrow->getShape() === $this) {
                $eyebrow->setShape(null);
            }
        }

        return $this;
    }
}
