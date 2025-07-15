<?php

namespace App\DTO\Avatar\Color;

final class ColorDTO
{
    private ?string $name;

    private ?string $hexa;

    /**
     * Get the value of name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the value of name.
     */
    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of hexa.
     */
    public function getHexa(): string
    {
        return $this->hexa;
    }

    /**
     * Set the value of hexa.
     */
    public function setHexa(?string $hexa): self
    {
        $this->hexa = $hexa;

        return $this;
    }
}
