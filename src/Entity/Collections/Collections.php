<?php

namespace App\Entity\Collections;

use App\Entity\Clothes\Clothes;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Category\Category;
use App\Entity\Traits\DateFieldsTrait;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use App\Repository\Collections\CollectionsRepository;

#[ORM\Entity(repositoryClass: CollectionsRepository::class)]
class Collections
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column]
    private ?bool $isOnline = null;

    #[ORM\ManyToOne(inversedBy: 'Collections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(nullable: true)]
    private ?array $sizeguid = null;

    /**
     * @var Collection<int, Clothes>
     */
    #[ORM\OneToMany(targetEntity: Clothes::class, mappedBy: 'Collection', orphanRemoval: true)]
    private Collection $clothes;

    public function __construct()
    {
        $this->clothes = new ArrayCollection();
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

    public function isOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getSizeguid(): ?array
    {
        return $this->sizeguid;
    }

    public function setSizeguid(?array $sizeguid): static
    {
        $this->sizeguid = $sizeguid;

        return $this;
    }

    /**
     * @return Collection<int, Clothes>
     */
    public function getClothes(): Collection
    {
        return $this->clothes;
    }

    public function addClothes(Clothes $clothes): static
    {
        if (!$this->clothes->contains($clothes)) {
            $this->clothes->add($clothes);
            $clothes->setCollection($this);
        }

        return $this;
    }

    public function removeClothes(Clothes $clothes): static
    {
        if ($this->clothes->removeElement($clothes)) {
            // set the owning side to null (unless already changed)
            if ($clothes->getCollection() === $this) {
                $clothes->setCollection(null);
            }
        }

        return $this;
    }
}
