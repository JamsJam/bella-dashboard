<?php

namespace App\Entity\Avatar\Body;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Body\MorphotypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MorphotypeRepository::class)]
#[ApiResource]
class Morphotype
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, Body>
     */
    #[ORM\OneToMany(targetEntity: Body::class, mappedBy: 'morphotype', orphanRemoval: true)]
    private Collection $bodies;

    public function __construct()
    {
        $this->bodies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

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
            $body->setMorphotype($this);
        }

        return $this;
    }

    public function removeBody(Body $body): static
    {
        if ($this->bodies->removeElement($body)) {
            // set the owning side to null (unless already changed)
            if ($body->getMorphotype() === $this) {
                $body->setMorphotype(null);
            }
        }

        return $this;
    }
}
