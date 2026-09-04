<?php

namespace App\Entity\Clothes;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Avatar\Body\Body;
use App\Entity\Collections\Collections;
use App\Entity\SizeGuide;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Clothes\ClothesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClothesRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CLOTHES_NAME', columns: ['name'])]
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

    #[ORM\Column(nullable: true)]
    private ?int $price = null;

    #[ORM\ManyToOne(inversedBy: 'clothes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Collections $Collection = null;

    /**
     * @var Collection<int, ClothesVariant>
     */
    #[ORM\OneToMany(targetEntity: ClothesVariant::class, mappedBy: 'clothes', cascade: ['persist'], orphanRemoval: true)]
    private Collection $variants;

    public function __construct()
    {
        $this->variants = new ArrayCollection();
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
        return $this->getFirstVariant()?->getDescription();
    }

    public function setDescription(?string $description): static
    {
        foreach ($this->variants as $variant) {
            $variant->setDescription($description);
        }

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

    public function getImages(): ?array
    {
        return $this->getFirstVariant()?->getImages();
    }

    public function setImages(?array $images): static
    {
        foreach ($this->variants as $variant) {
            $variant->setImages($images);
        }

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
        return $this->getFirstVariant()?->getColor();
    }

    public function setColor(?Clothescolor $color): static
    {
        if ($color instanceof Clothescolor) {
            $this->ensureFirstVariant()->setColor($color);
        }

        return $this;
    }

    public function getMetadescription(): ?string
    {
        return $this->getFirstVariant()?->getMetadescription();
    }

    public function setMetadescription(?string $metadescription): static
    {
        foreach ($this->variants as $variant) {
            $variant->setMetadescription($metadescription);
        }

        return $this;
    }

    public function getSize(): ?Clothessize
    {
        return $this->getFirstVariant()?->getSize();
    }

    public function setSize(?Clothessize $size): static
    {
        if ($size instanceof Clothessize) {
            $this->ensureFirstVariant()->setSize($size);
        }

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->getFirstVariant()?->getSku();
    }

    public function setSku(string $sku): static
    {
        $this->ensureFirstVariant()->setSku($sku);

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->getFirstVariant()?->getStock();
    }

    public function setStock(?int $stock): static
    {
        $this->ensureFirstVariant()->setStock((int) $stock);

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->getFirstVariant()?->getSlug();
    }

    public function setSlug(string $slug): static
    {
        $this->ensureFirstVariant()->setSlug($slug);

        return $this;
    }

    public function hasOnlineVariant(): bool
    {
        foreach ($this->variants as $variant) {
            if (\App\Enum\ClotheStatus::Online === $variant->getPublicationStatus()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, Body>
     */
    public function getBodies(): Collection
    {
        $bodies = [];

        foreach ($this->variants as $variant) {
            foreach ($variant->getBodies() as $body) {
                if (null !== $body->getId()) {
                    $bodies[$body->getId()] = $body;
                    continue;
                }

                $bodies[] = $body;
            }
        }

        return new ArrayCollection(array_values($bodies));
    }

    public function addBody(Body $body): static
    {
        foreach ($this->variants as $variant) {
            $variant->addBody($body);
        }

        return $this;
    }

    public function removeBody(Body $body): static
    {
        foreach ($this->variants as $variant) {
            $variant->removeBody($body);
        }

        return $this;
    }

    public function isBestseller(): ?bool
    {
        foreach ($this->variants as $variant) {
            if ($variant->isBestseller()) {
                return true;
            }
        }

        return false;
    }

    public function setIsBestseller(bool $isBestseller): static
    {
        foreach ($this->variants as $variant) {
            $variant->setIsBestseller($isBestseller);
        }

        return $this;
    }

    public function isInCarousel(): ?bool
    {
        foreach ($this->variants as $variant) {
            if ($variant->isInCarousel()) {
                return true;
            }
        }

        return false;
    }

    public function setIsInCarousel(bool $isInCarousel): static
    {
        foreach ($this->variants as $variant) {
            $variant->setIsInCarousel($isInCarousel);
        }

        return $this;
    }

    public function getSizeGuide(): ?SizeGuide
    {
        return $this->getFirstVariant()?->getSizeGuide();
    }

    public function setSizeGuide(?SizeGuide $sizeGuide): static
    {
        foreach ($this->variants as $variant) {
            $variant->setSizeGuide($sizeGuide);
        }

        return $this;
    }

    /**
     * @return Collection<int, ClothesVariant>
     */
    public function getVariants(): Collection
    {
        return $this->variants;
    }

    public function addVariant(ClothesVariant $variant): static
    {
        if (!$this->variants->contains($variant)) {
            $this->variants->add($variant);
            $variant->setClothes($this);
        }

        return $this;
    }

    public function removeVariant(ClothesVariant $variant): static
    {
        if ($this->variants->removeElement($variant) && $variant->getClothes() === $this) {
            $variant->setClothes(null);
        }

        return $this;
    }

    public function getTotalStock(): int
    {
        $total = 0;

        foreach ($this->variants as $variant) {
            $total += max(0, (int) $variant->getStock());
        }

        return $total;
    }

    public function hasAvailableVariant(): bool
    {
        foreach ($this->variants as $variant) {
            if ($variant->isAvailable()) {
                return true;
            }
        }

        return false;
    }

    private function getFirstVariant(): ?ClothesVariant
    {
        $variant = $this->variants->first();

        return $variant instanceof ClothesVariant ? $variant : null;
    }

    private function ensureFirstVariant(): ClothesVariant
    {
        $variant = $this->getFirstVariant();

        if (!$variant instanceof ClothesVariant) {
            $variant = new ClothesVariant();
            $this->addVariant($variant);
        }

        return $variant;
    }
}
