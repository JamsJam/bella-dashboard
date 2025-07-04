<?php

namespace App\Entity\Avatar\Hairs;

use App\Entity\Traits\ColorFieldsTrait;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Hairs\HairscolorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HairscolorRepository::class)]
class Hairscolor
{
    use ColorFieldsTrait;
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, Hairs>
     */
    #[ORM\OneToMany(targetEntity: Hairs::class, mappedBy: 'color', orphanRemoval: true)]
    private Collection $hairs;

    public function __construct()
    {
        $this->hairs = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, Hairs>
     */
    public function getHairs(): Collection
    {
        return $this->hairs;
    }

    public function addHair(Hairs $hair): static
    {
        if (!$this->hairs->contains($hair)) {
            $this->hairs->add($hair);
            $hair->setColor($this);
        }

        return $this;
    }

    public function removeHair(Hairs $hair): static
    {
        if ($this->hairs->removeElement($hair)) {
            // set the owning side to null (unless already changed)
            if ($hair->getColor() === $this) {
                $hair->setColor(null);
            }
        }

        return $this;
    }


}
