<?php

namespace App\Entity\Orders;

use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Users\Customers;
use App\Repository\Orders\OrdersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrdersRepository::class)]
class Orders
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_PAID = 'paid';
    public const STATUS_PAYMENT_EXPIRED = 'payment_expired';
    public const STATUS_CHECKOUT_CREATION_FAILED = 'checkout_creation_failed';

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

    #[ORM\OneToOne(inversedBy: 'order', targetEntity: Cart::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;

    #[ORM\Column]
    private ?int $tva = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeInvoiceId = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $stripeInvoiceUrl = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->setCreatedAt($now);
        $this->setEditedAt($now);
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
        $this->setEditedAt(new \DateTimeImmutable());

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

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(Cart $cart): static
    {
        $this->cart = $cart;
        $cart->setOrder($this);

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

    public function getStripeCheckoutSessionId(): ?string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(?string $stripeCheckoutSessionId): static
    {
        $this->stripeCheckoutSessionId = $stripeCheckoutSessionId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getStripeInvoiceId(): ?string
    {
        return $this->stripeInvoiceId;
    }

    public function setStripeInvoiceId(?string $stripeInvoiceId): static
    {
        $this->stripeInvoiceId = $stripeInvoiceId;

        return $this;
    }

    public function getStripeInvoiceUrl(): ?string
    {
        return $this->stripeInvoiceUrl;
    }

    public function setStripeInvoiceUrl(?string $stripeInvoiceUrl): static
    {
        $this->stripeInvoiceUrl = $stripeInvoiceUrl;

        return $this;
    }
}
