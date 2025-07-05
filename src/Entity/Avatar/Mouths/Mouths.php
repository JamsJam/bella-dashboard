<?php

namespace App\Entity\Avatar\Mouths;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Avatar\Mouths\MouthsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouthsRepository::class)]
class Mouths
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

    #[ORM\ManyToOne(inversedBy: 'mouths')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Mouthshape $shape = null;

    #[ORM\ManyToOne(inversedBy: 'mouths')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Mouthscolor $color = null;

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

    public function getShape(): ?Mouthshape
    {
        return $this->shape;
    }

    public function setShape(?Mouthshape $shape): static
    {
        $this->shape = $shape;

        return $this;
    }

    public function getColor(): ?Mouthscolor
    {
        return $this->color;
    }

    public function setColor(?Mouthscolor $color): static
    {
        $this->color = $color;

        return $this;
    }
}
