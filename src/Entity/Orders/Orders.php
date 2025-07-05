<?php

namespace App\Entity\Orders;

use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Users\Customers;
use App\Repository\Orders\OrdersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdersRepository::class)]
class Orders
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $subtotal = null;

    #[ORM\Column]
    private ?int $total = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?Customers $customer = null;

    #[ORM\Column(length: 255)]
    private ?string $orderReference = null;

    #[ORM\Column]
    private ?int $fees = null;

    #[ORM\Column]
    private array $shippinfo = [];

    /**
     * @var Collection<int, Cart>
     */
    #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'orders', orphanRemoval: true)]
    private Collection $cart;

    #[ORM\Column]
    private ?int $tva = null;

    public function __construct()
    {
        $this->cart = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubtotal(): ?int
    {
        return $this->subtotal;
    }

    public function setSubtotal(int $subtotal): static
    {
        $this->subtotal = $subtotal;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
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

    public function getOrderReference(): ?string
    {
        return $this->orderReference;
    }

    public function setOrderReference(string $orderReference): static
    {
        $this->orderReference = $orderReference;

        return $this;
    }

    public function getFees(): ?int
    {
        return $this->fees;
    }

    public function setFees(int $fees): static
    {
        $this->fees = $fees;

        return $this;
    }

    public function getShippinfo(): array
    {
        return $this->shippinfo;
    }

    public function setShippinfo(array $shippinfo): static
    {
        $this->shippinfo = $shippinfo;

        return $this;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCart(): Collection
    {
        return $this->cart;
    }

    public function addCart(Cart $cart): static
    {
        if (!$this->cart->contains($cart)) {
            $this->cart->add($cart);
            $cart->setOrders($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): static
    {
        if ($this->cart->removeElement($cart)) {
            // set the owning side to null (unless already changed)
            if ($cart->getOrders() === $this) {
                $cart->setOrders(null);
            }
        }

        return $this;
    }

    public function getTva(): ?int
    {
        return $this->tva;
    }

    public function setTva(int $tva): static
    {
        $this->tva = $tva;

        return $this;
    }
}
