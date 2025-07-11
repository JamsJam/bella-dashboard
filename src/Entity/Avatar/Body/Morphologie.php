<?php

namespace App\Entity\Avatar\Body;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Body\MorphologieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MorphologieRepository::class)]
class Morphologie
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, Morphotype>
     */
    #[ORM\OneToMany(targetEntity: Morphotype::class, mappedBy: 'morphologie', orphanRemoval: true)]
    private Collection $morphotypes;

    public function __construct()
    {
        $this->morphotypes = new ArrayCollection();
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
     * @return Collection<int, Morphotype>
     */
    public function getMorphotypes(): Collection
    {
        return $this->morphotypes;
    }

    public function addMorphotype(Morphotype $morphotype): static
    {
        if (!$this->morphotypes->contains($morphotype)) {
            $this->morphotypes->add($morphotype);
            $morphotype->setMorphologie($this);
        }

        return $this;
    }

    public function removeMorphotype(Morphotype $morphotype): static
    {
        if ($this->morphotypes->removeElement($morphotype)) {
            // set the owning side to null (unless already changed)
            if ($morphotype->getMorphologie() === $this) {
                $morphotype->setMorphologie(null);
            }
        }

        return $this;
    }
}
