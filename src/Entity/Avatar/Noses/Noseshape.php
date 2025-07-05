<?php

namespace App\Entity\Avatar\Noses;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Noses\NoseshapeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoseshapeRepository::class)]
class Noseshape
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    /**
     * @var Collection<int, Nose>
     */
    #[ORM\OneToMany(targetEntity: Nose::class, mappedBy: 'shape', orphanRemoval: true)]
    private Collection $noses;

    public function __construct()
    {
        $this->noses = new ArrayCollection();
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
            $nose->setShape($this);
        }

        return $this;
    }

    public function removeNose(Nose $nose): static
    {
        if ($this->noses->removeElement($nose)) {
            // set the owning side to null (unless already changed)
            if ($nose->getShape() === $this) {
                $nose->setShape(null);
            }
        }

        return $this;
    }
}
