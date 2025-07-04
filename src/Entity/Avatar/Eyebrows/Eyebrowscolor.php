<?php

namespace App\Entity\Avatar\Eyebrows;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Traits\ColorFieldsTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Avatar\Eyebrows\EyebrowscolorRepository;

#[ORM\Entity(repositoryClass: EyebrowscolorRepository::class)]
class Eyebrowscolor
{
    use ColorFieldsTrait;
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;



    /**
     * @var Collection<int, Eyebrows>
     */
    #[ORM\OneToMany(targetEntity: Eyebrows::class, mappedBy: 'color', orphanRemoval: true)]
    private Collection $eyebrows;

    public function __construct()
    {
        $this->eyebrows = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $eyebrow->setColor($this);
        }

        return $this;
    }

    public function removeEyebrow(Eyebrows $eyebrow): static
    {
        if ($this->eyebrows->removeElement($eyebrow)) {
            // set the owning side to null (unless already changed)
            if ($eyebrow->getColor() === $this) {
                $eyebrow->setColor(null);
            }
        }

        return $this;
    }
}
