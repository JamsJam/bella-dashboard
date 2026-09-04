<?php

namespace App\Entity\Avatar\Faces;

use App\Entity\Avatar\Skincolor;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Faces\FacesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FacesRepository::class)]
class Faces
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\ManyToOne(inversedBy: 'faces')]
    private ?Skincolor $skincolor = null;

    #[ORM\Column(length: 64)]
    private ?string $checksum = null;

    #[ORM\ManyToOne(inversedBy: 'faces')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Faceshape $shape = null;

    #[ORM\ManyToOne(inversedBy: 'faces')]
    private ?FaceAccessory $accessory = null;

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

    public function setImage(string $image): static
    {
        $this->image = $image;

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

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): static
    {
        $this->checksum = $checksum;

        return $this;
    }

    public function getShape(): ?Faceshape
    {
        return $this->shape;
    }

    public function setShape(?Faceshape $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function getAccessory(): ?FaceAccessory
    {
        return $this->accessory;
    }

    public function setAccessory(?FaceAccessory $accessory): static
    {
        $this->accessory = $accessory;

        return $this;
    }
}
