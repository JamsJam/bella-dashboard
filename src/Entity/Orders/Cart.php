<?php

namespace App\Entity\Orders;

use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Users\Customers;
use App\Repository\Orders\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartRepository::class)]
class Cart
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customers $customer = null;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(targetEntity: CartItem::class, mappedBy: 'cart', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\OneToOne(mappedBy: 'cart', targetEntity: Orders::class)]
    private ?Orders $order = null;

    #[ORM\Column(length: 3)]
    private string $currency = 'eur';

    #[ORM\Column]
    private int $subtotal = 0;

    #[ORM\Column]
    private int $total = 0;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $now = new \DateTimeImmutable();
        $this->setCreatedAt($now);
        $this->setEditedAt($now);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customers
    {
        return $this->customer;
    }

    public function setCustomer(?Customers $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCart($this);
            $this->recalculateTotals();
        }

        return $this;
    }

    public function removeItem(CartItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCart() === $this) {
                $item->setCart(null);
            }
            $this->recalculateTotals();
        }

        return $this;
    }

    public function getOrder(): ?Orders
    {
        return $this->order;
    }

    public function setOrder(?Orders $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = strtolower($currency);

        return $this;
    }

    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function recalculateTotals(): void
    {
        $total = 0;

        foreach ($this->items as $item) {
            $total += $item->getTotalTTC();
        }

        $this->subtotal = $total;
        $this->total = $total;
    }
}
