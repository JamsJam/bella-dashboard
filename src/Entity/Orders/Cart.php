<?php

namespace App\Entity\Orders;

use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Orders\CartRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $productReference = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\Column]
    private ?int $unitPriceHt = null;

    #[ORM\Column]
    private ?int $unitPriceTTC = null;

    #[ORM\ManyToOne(inversedBy: 'cart')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Orders $orders = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductReference(): ?string
    {
        return $this->productReference;
    }

    public function setProductReference(string $productReference): static
    {
        $this->productReference = $productReference;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnitPriceHt(): ?int
    {
        return $this->unitPriceHt;
    }

    public function setUnitPriceHt(int $unitPriceHt): static
    {
        $this->unitPriceHt = $unitPriceHt;

        return $this;
    }

    public function getUnitPriceTTC(): ?int
    {
        return $this->unitPriceTTC;
    }

    public function setUnitPriceTTC(int $unitPriceTTC): static
    {
        $this->unitPriceTTC = $unitPriceTTC;

        return $this;
    }

    public function getOrders(): ?Orders
    {
        return $this->orders;
    }

    public function setOrders(?Orders $orders): static
    {
        $this->orders = $orders;

        return $this;
    }
}
