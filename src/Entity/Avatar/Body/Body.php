<?php

namespace App\Entity\Avatar\Body;

use App\Entity\Avatar\Skincolor;
use App\Entity\Clothes\Clothes;
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
     * @var Collection<int, Clothes>
     */
    #[ORM\ManyToMany(targetEntity: Clothes::class, inversedBy: 'bodies')]
    #[ORM\JoinTable(name: 'body_clothes')]
    private Collection $clothes;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 64)]
    private ?string $checksum = null;

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
        return $this->clothes->first() ?: null;
    }

    public function setClothe(?Clothes $clothe): static
    {
        foreach ($this->clothes as $currentClothe) {
            $currentClothe->getBodies()->removeElement($this);
        }

        $this->clothes->clear();

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
        return $this->clothes;
    }

    public function addClothe(Clothes $clothe): static
    {
        if (!$this->clothes->contains($clothe)) {
            $this->clothes->add($clothe);

            if (!$clothe->getBodies()->contains($this)) {
                $clothe->getBodies()->add($this);
            }
        }

        return $this;
    }

    public function removeClothe(Clothes $clothe): static
    {
        if ($this->clothes->removeElement($clothe)) {
            $clothe->getBodies()->removeElement($this);
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
