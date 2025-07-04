<?php

namespace App\Entity\Category;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Collections\Collections;
use Doctrine\Common\Collections\Collection;
use App\Repository\Category\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;



    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column]
    private ?bool $isOnline = null;

    /**
     * @var Collection<int, Collections>
     */
    #[ORM\OneToMany(targetEntity: Collections::class, mappedBy: 'category', orphanRemoval: true)]
    private Collection $collections;

    public function __construct()
    {
        $this->collections = new ArrayCollection();
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

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

    /**
     * @return Collection<int, Collections>
     */
    public function getCollections(): Collection
    {
        return $this->collections;
    }

    public function addCollections(Collections $collections): static
    {
        if (!$this->collections->contains($collections)) {
            $this->collections->add($collections);
            $collections->setCategory($this);
        }

        return $this;
    }

    public function removeCollections(Collections $collections): static
    {
        if ($this->collections->removeElement($collections)) {
            // set the owning side to null (unless already changed)
            if ($collections->getCategory() === $this) {
                $collections->setCategory(null);
            }
        }

        return $this;
    }
}
