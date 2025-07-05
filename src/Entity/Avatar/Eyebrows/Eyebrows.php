<?php

namespace App\Entity\Avatar\Eyebrows;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Eyebrows\EyebrowsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EyebrowsRepository::class)]
class Eyebrows
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

    #[ORM\ManyToOne(inversedBy: 'eyebrows')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Eyebrowshape $shape = null;

    #[ORM\ManyToOne(inversedBy: 'eyebrows')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Eyebrowscolor $color = null;

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

    public function getShape(): ?Eyebrowshape
    {
        return $this->shape;
    }

    public function setShape(?Eyebrowshape $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function getColor(): ?Eyebrowscolor
    {
        return $this->color;
    }

    public function setColor(?Eyebrowscolor $color): static
    {
        $this->color = $color;

        return $this;
    }
}
