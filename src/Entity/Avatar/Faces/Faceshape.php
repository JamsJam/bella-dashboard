<?php

namespace App\Entity\Avatar\Faces;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DateFieldsTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Avatar\Faces\FaceshapeRepository;

#[ORM\Entity(repositoryClass: FaceshapeRepository::class)]
class Faceshape
{
    use DateFieldsTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, Faces>
     */
    #[ORM\OneToMany(targetEntity: Faces::class, mappedBy: 'shape', orphanRemoval: true)]
    private Collection $faces;

    public function __construct()
    {
        $this->faces = new ArrayCollection();
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
     * @return Collection<int, Faces>
     */
    public function getFaces(): Collection
    {
        return $this->faces;
    }

    public function addFace(Faces $face): static
    {
        if (!$this->faces->contains($face)) {
            $this->faces->add($face);
            $face->setShape($this);
        }

        return $this;
    }

    public function removeFace(Faces $face): static
    {
        if ($this->faces->removeElement($face)) {
            // set the owning side to null (unless already changed)
            if ($face->getShape() === $this) {
                $face->setShape(null);
            }
        }

        return $this;
    }
}
