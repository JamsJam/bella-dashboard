<?php

namespace App\Entity\Avatar\Eyes;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Eyes\EyeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EyeRepository::class)]
class Eye
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

    #[ORM\ManyToOne(inversedBy: 'eyes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Eyeshape $shape = null;

    #[ORM\ManyToOne(inversedBy: 'eyes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Eyecolor $color = null;

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

    public function getShape(): ?Eyeshape
    {
        return $this->shape;
    }

    public function setShape(?Eyeshape $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function getColor(): ?Eyecolor
    {
        return $this->color;
    }

    public function setColor(?Eyecolor $color): static
    {
        $this->color = $color;

        return $this;
    }
}
