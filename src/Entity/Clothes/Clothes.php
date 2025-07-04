<?php

namespace App\Entity\Clothes;

use App\Entity\Avatar\Body\Body;
use App\Entity\Collections\Collections;
use App\Repository\Clothes\ClothesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClothesRepository::class)]
class Clothes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $price = null;

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(nullable: true)]
    private ?array $images = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collections $Collection = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Clothescolor $color = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $metadescription = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    private ?Clothessize $size = null;

    #[ORM\Column(length: 100)]
    private ?string $sku = null;

    #[ORM\Column(length: 70)]
    private ?string $slug = null;

    #[ORM\Column(length: 40)]
    private ?string $status = null;

    #[ORM\Column]
    private ?bool $isOnline = null;

    /**
     * @var Collection<int, Body>
     */
    #[ORM\OneToMany(targetEntity: Body::class, mappedBy: 'clothe')]
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

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(?int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): static
    {
        $this->images = $images;

        return $this;
    }

    public function getCollection(): ?Collections
    {
        return $this->Collection;
    }

    public function setCollection(?Collections $Collection): static
    {
        $this->Collection = $Collection;

        return $this;
    }

    public function getColor(): ?Clothescolor
    {
        return $this->color;
    }

    public function setColor(?Clothescolor $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getMetadescription(): ?string
    {
        return $this->metadescription;
    }

    public function setMetadescription(?string $metadescription): static
    {
        $this->metadescription = $metadescription;

        return $this;
    }

    public function getSize(): ?Clothessize
    {
        return $this->size;
    }

    public function setSize(?Clothessize $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
            $body->setClothe($this);
        }

        return $this;
    }

    public function removeBody(Body $body): static
    {
        if ($this->bodies->removeElement($body)) {
            // set the owning side to null (unless already changed)
            if ($body->getClothe() === $this) {
                $body->setClothe(null);
            }
        }

        return $this;
    }
}
