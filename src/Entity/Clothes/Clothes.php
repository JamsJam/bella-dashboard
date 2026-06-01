<?php

namespace App\Entity\Clothes;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\SizeGuide;
use Doctrine\DBAL\Types\Types;
use App\Entity\Avatar\Body\Body;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Collections\Collections;
use Doctrine\Common\Collections\Collection;
use App\Repository\Clothes\ClothesRepository;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: ClothesRepository::class)]
#[ApiResource()]
class Clothes
{
    use DateFieldsTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 70)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $price = null;

    #[ORM\Column(nullable: true)]
    private ?int $stock = null;

    #[ORM\Column(nullable: true)]
    private ?array $images = null;

    #[ORM\ManyToOne(inversedBy: 'clothes' )]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collections $collection = null;

    #[ORM\ManyToOne(inversedBy: 'clothes' ,cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Clothescolor $color = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $metadescription = null;

    #[ORM\ManyToOne(inversedBy: 'clothes' ,cascade: ['persist'])]
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

    #[ORM\Column]
    private ?bool $isBestseller = null;

    #[ORM\Column]
    private ?bool $isInCarousel = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SizeGuide $sizeGuide = null;

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
        return $this->collection;
    }

    public function setCollection(?Collections $collection): static
    {
        $this->collection = $collection;

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

    public function isBestseller(): ?bool
    {
        return $this->isBestseller;
    }

    public function setIsBestseller(bool $isBestseller): static
    {
        $this->isBestseller = $isBestseller;

        return $this;
    }

    public function isInCarousel(): ?bool
    {
        return $this->isInCarousel;
    }

    public function setIsInCarousel(bool $isInCarousel): static
    {
        $this->isInCarousel = $isInCarousel;

        return $this;
    }

    public function getSizeGuide(): ?SizeGuide
    {
        return $this->sizeGuide;
    }

    public function setSizeGuide(?SizeGuide $sizeGuide): static
    {
        $this->sizeGuide = $sizeGuide;

        return $this;
    }

}
