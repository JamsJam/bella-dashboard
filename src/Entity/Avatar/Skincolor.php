<?php

namespace App\Entity\Avatar;

use App\Entity\Avatar\Body\Body;
use App\Entity\Avatar\Faces\Faces;
use App\Entity\Avatar\Noses\Nose;
use App\Entity\Traits\ColorFieldsTrait;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\SkincolorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkincolorRepository::class)]
class Skincolor
{
    use ColorFieldsTrait;
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    /**
     * @var Collection<int, Nose>
     */
    #[ORM\OneToMany(targetEntity: Nose::class, mappedBy: 'skincolor', orphanRemoval: true)]
    private Collection $noses;

    /**
     * @var Collection<int, Body>
     */
    #[ORM\OneToMany(targetEntity: Body::class, mappedBy: 'skincolor', orphanRemoval: true)]
    private Collection $bodies;

    /**
     * @var Collection<int, Faces>
     */
    #[ORM\OneToMany(targetEntity: Faces::class, mappedBy: 'skincolor')]
    private Collection $faces;

    public function __construct()
    {
        $this->noses = new ArrayCollection();
        $this->bodies = new ArrayCollection();
        $this->faces = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }



    /**
     * @return Collection<int, Nose>
     */
    public function getNoses(): Collection
    {
        return $this->noses;
    }

    public function addNose(Nose $nose): static
    {
        if (!$this->noses->contains($nose)) {
            $this->noses->add($nose);
            $nose->setSkincolor($this);
        }

        return $this;
    }

    public function removeNose(Nose $nose): static
    {
        if ($this->noses->removeElement($nose)) {
            // set the owning side to null (unless already changed)
            if ($nose->getSkincolor() === $this) {
                $nose->setSkincolor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Body>
     */
    public function getBodies(): Collection
    {
        return $this->bodies;
    }

    public function addBody(Body $body): static
    {
        if (!$this->bodies->contains($body)) {
            $this->bodies->add($body);
            $body->setSkincolor($this);
        }

        return $this;
    }

    public function removeBody(Body $body): static
    {
        if ($this->bodies->removeElement($body)) {
            // set the owning side to null (unless already changed)
            if ($body->getSkincolor() === $this) {
                $body->setSkincolor(null);
            }
        }

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
            $face->setSkincolor($this);
        }

        return $this;
    }

    public function removeFace(Faces $face): static
    {
        if ($this->faces->removeElement($face)) {
            // set the owning side to null (unless already changed)
            if ($face->getSkincolor() === $this) {
                $face->setSkincolor(null);
            }
        }

        return $this;
    }
}
