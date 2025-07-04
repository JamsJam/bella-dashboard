<?php

namespace App\Entity\Avatar\Mouths;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Avatar\Mouths\Mouths;
use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Traits\ColorFieldsTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Avatar\Mouths\MouthscolorRepository;

#[ORM\Entity(repositoryClass: MouthscolorRepository::class)]
class Mouthscolor
{
    use ColorFieldsTrait;
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    /**
     * @var Collection<int, Mouths>
     */
    #[ORM\OneToMany(targetEntity: Mouths::class, mappedBy: 'color', orphanRemoval: true)]
    private Collection $mouths;

    public function __construct()
    {
        $this->mouths = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $mouth->setColor($this);
        }

        return $this;
    }

    public function removeMouth(Mouths $mouth): static
    {
        if ($this->mouths->removeElement($mouth)) {
            // set the owning side to null (unless already changed)
            if ($mouth->getColor() === $this) {
                $mouth->setColor(null);
            }
        }

        return $this;
    }
}
