<?php

namespace App\Entity\Avatar\Body;

use App\Entity\Avatar\Skincolor;
use App\Entity\Clothes\Clothes;
use App\Entity\Clothes\ClothesVariant;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Body\BodyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BodyRepository::class)]
class Body
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'bodies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Skincolor $skincolor = null;

    #[ORM\ManyToOne(inversedBy: 'bodies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Morphotype $morphotype = null;

    /**
     * @var Collection<int, ClothesVariant>
     */
    #[ORM\ManyToMany(targetEntity: ClothesVariant::class, mappedBy: 'bodies')]
    private Collection $clothesVariants;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 64)]
    private ?string $checksum = null;

    public function __construct()
    {
        $this->clothesVariants = new ArrayCollection();
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

    public function getSkincolor(): ?Skincolor
    {
        return $this->skincolor;
    }

    public function setSkincolor(?Skincolor $skincolor): static
    {
        $this->skincolor = $skincolor;

        return $this;
    }

    public function getMorphotype(): ?Morphotype
    {
        return $this->morphotype;
    }

    public function setMorphotype(?Morphotype $morphotype): static
    {
        $this->morphotype = $morphotype;

        return $this;
    }

    public function getClothe(): ?Clothes
    {
        $variant = $this->clothesVariants->first();

        return $variant instanceof ClothesVariant ? $variant->getClothes() : null;
    }

    public function setClothe(?Clothes $clothe): static
    {
        foreach (clone $this->clothesVariants as $variant) {
            $this->removeClothesVariant($variant);
        }

        if ($clothe instanceof Clothes) {
            $this->addClothe($clothe);
        }

        return $this;
    }

    /**
     * @return Collection<int, Clothes>
     */
    public function getClothes(): Collection
    {
        $clothes = [];

        foreach ($this->clothesVariants as $variant) {
            $clothe = $variant->getClothes();
            if (!$clothe instanceof Clothes) {
                continue;
            }

            if ($clothe->getId() !== null) {
                $clothes[$clothe->getId()] = $clothe;
                continue;
            }

            $clothes[] = $clothe;
        }

        return new ArrayCollection(array_values($clothes));
    }

    public function addClothe(Clothes $clothe): static
    {
        foreach ($clothe->getVariants() as $variant) {
            $this->addClothesVariant($variant);
        }

        return $this;
    }

    public function removeClothe(Clothes $clothe): static
    {
        foreach ($clothe->getVariants() as $variant) {
            $this->removeClothesVariant($variant);
        }

        return $this;
    }

    /**
     * @return Collection<int, ClothesVariant>
     */
    public function getClothesVariants(): Collection
    {
        return $this->clothesVariants;
    }

    public function addClothesVariant(ClothesVariant $variant): static
    {
        if (!$this->clothesVariants->contains($variant)) {
            $this->clothesVariants->add($variant);

            if (!$variant->getBodies()->contains($this)) {
                $variant->getBodies()->add($this);
            }
        }

        return $this;
    }

    public function removeClothesVariant(ClothesVariant $variant): static
    {
        if ($this->clothesVariants->removeElement($variant)) {
            $variant->getBodies()->removeElement($this);
        }

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): static
    {
        $this->checksum = $checksum;

        return $this;
    }
}
