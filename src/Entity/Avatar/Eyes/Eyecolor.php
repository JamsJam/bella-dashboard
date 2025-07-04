<?php

namespace App\Entity\Avatar\Eyes;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Traits\ColorFieldsTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Avatar\Eyes\EyecolorRepository;

#[ORM\Entity(repositoryClass: EyecolorRepository::class)]
class Eyecolor
{
    use ColorFieldsTrait;
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;



    /**
     * @var Collection<int, Eye>
     */
    #[ORM\OneToMany(targetEntity: Eye::class, mappedBy: 'color', orphanRemoval: true)]
    private Collection $eyes;

    public function __construct()
    {
        $this->eyes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
            $eye->setColor($this);
        }

        return $this;
    }

    public function removeEye(Eye $eye): static
    {
        if ($this->eyes->removeElement($eye)) {
            // set the owning side to null (unless already changed)
            if ($eye->getColor() === $this) {
                $eye->setColor(null);
            }
        }

        return $this;
    }
}
