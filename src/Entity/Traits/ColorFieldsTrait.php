<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;

trait ColorFieldsTrait
{
    #[ORM\Column(length: 50)]
    private ?string $name = null;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $hexa = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getHexa(): ?string
    {
        return $this->hexa;
    }

    public function setHexa(?string $hexa): static
    {
        $this->hexa = $hexa;

        return $this;
    }
}
