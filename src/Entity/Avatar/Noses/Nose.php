<?php

namespace App\Entity\Avatar\Noses;

use App\Entity\Avatar\Skincolor;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Noses\NoseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoseRepository::class)]
class Nose
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

    #[ORM\Column(length: 64)]
    private ?string $checksum = null;

    #[ORM\ManyToOne(inversedBy: 'noses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Skincolor $skincolor = null;

    #[ORM\ManyToOne(inversedBy: 'noses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Noseshape $shape = null;

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

    public function getChecksum(): ?string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): static
    {
        $this->checksum = $checksum;

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

    public function getShape(): ?Noseshape
    {
        return $this->shape;
    }

    public function setShape(?Noseshape $shape): static
    {
        $this->shape = $shape;

        return $this;
    }
}
