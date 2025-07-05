<?php

namespace App\Entity\Avatar\Hairs;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Hairs\HairsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HairsRepository::class)]
class Hairs
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column]
    private array $images = [];

    #[ORM\Column(length: 64)]
    private ?string $checksum = null;

    #[ORM\ManyToOne(inversedBy: 'hairs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hairscolor $color = null;

    #[ORM\ManyToOne(inversedBy: 'hairs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Hairshape $shape = null;

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

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): static
    {
        $this->images = $images;

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

    public function getColor(): ?Hairscolor
    {
        return $this->color;
    }

    public function setColor(?Hairscolor $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getShape(): ?Hairshape
    {
        return $this->shape;
    }

    public function setShape(?Hairshape $shape): static
    {
        $this->shape = $shape;

        return $this;
    }
}
