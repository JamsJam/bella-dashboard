<?php

namespace App\Entity\Avatar\Hairs;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Hairs\HairshapeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HairshapeRepository::class)]
class Hairshape
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    /**
     * @var Collection<int, Hairs>
     */
    #[ORM\OneToMany(targetEntity: Hairs::class, mappedBy: 'shape', orphanRemoval: true)]
    private Collection $hairs;

    public function __construct()
    {
        $this->hairs = new ArrayCollection();
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
            $hair->setShape($this);
        }

        return $this;
    }

    public function removeHair(Hairs $hair): static
    {
        if ($this->hairs->removeElement($hair)) {
            // set the owning side to null (unless already changed)
            if ($hair->getShape() === $this) {
                $hair->setShape(null);
            }
        }

        return $this;
    }
}
