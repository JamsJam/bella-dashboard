<?php

namespace App\Entity\Users;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Entity\Orders\Orders;
use App\Entity\Traits\DateFieldsTrait;
use App\Entity\Traits\UserFieldsTrait;
use App\State\Users\CustomerPasswordHasherProcessor;
use App\Repository\Users\CustomersRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [
        new Post(processor: CustomerPasswordHasherProcessor::class),         // inscription
        new Put(),          // modification de son profil
    ],
    normalizationContext: ['groups' => ['customer:read']],
    denormalizationContext: ['groups' => ['customer:write']]
)]
#[ORM\Entity(repositoryClass: CustomersRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Customers implements UserInterface, PasswordAuthenticatedUserInterface
{
    use DateFieldsTrait;
    use UserFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['customer:read'])]
    private ?int $id = null;

    /**
     * @var Collection<int, Orders>
     */
    #[ORM\OneToMany(targetEntity: Orders::class, mappedBy: 'customer')]
    private Collection $orders;

    #[ORM\Column]
    private bool $isSignupConfirmed = true;

    #[ORM\Column(length: 6, nullable: true)]
    private ?string $signupVerificationCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $signupVerificationExpiresAt = null;

    public function __construct()
    {
        $this->orders = new ArrayCollection();
    }

    /**
     * @return Collection<int, Orders>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Orders $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setCustomer($this);
        }

        return $this;
    }

    public function removeOrder(Orders $order): static
    {
        if ($this->orders->removeElement($order)) {
            // set the owning side to null (unless already changed)
            if ($order->getCustomer() === $this) {
                $order->setCustomer(null);
            }
        }

        return $this;
    }

    public function isSignupConfirmed(): bool
    {
        return $this->isSignupConfirmed;
    }

    public function setIsSignupConfirmed(bool $isSignupConfirmed): static
    {
        $this->isSignupConfirmed = $isSignupConfirmed;

        return $this;
    }

    public function getSignupVerificationCode(): ?string
    {
        return $this->signupVerificationCode;
    }

    public function setSignupVerificationCode(?string $signupVerificationCode): static
    {
        $this->signupVerificationCode = $signupVerificationCode;

        return $this;
    }

    public function getSignupVerificationExpiresAt(): ?\DateTimeImmutable
    {
        return $this->signupVerificationExpiresAt;
    }

    public function setSignupVerificationExpiresAt(?\DateTimeImmutable $signupVerificationExpiresAt): static
    {
        $this->signupVerificationExpiresAt = $signupVerificationExpiresAt;

        return $this;
    }
}
