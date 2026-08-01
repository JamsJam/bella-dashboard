<?php

namespace App\Entity\Reviews;

use App\Entity\Clothes\ClothesVariant;
use App\Entity\Orders\Orders;
use App\Entity\Users\Customers;
use App\Enum\ReviewStatus;
use App\Repository\Reviews\ReviewRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
#[ORM\Table(name: 'review')]
#[ORM\UniqueConstraint(name: 'UNIQ_REVIEW_UUID', columns: ['review_uuid'])]
#[ORM\UniqueConstraint(name: 'UNIQ_REVIEW_ORDER_PRODUCT_CUSTOMER', columns: ['product_id', 'order_id', 'customers_id'])]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'review_uuid', length: 36)]
    private string $reviewUuid;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'product_id', nullable: false, onDelete: 'CASCADE')]
    private ClothesVariant $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'order_id', nullable: false, onDelete: 'CASCADE')]
    private Orders $order;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'customers_id', nullable: false, onDelete: 'CASCADE')]
    private Customers $customer;

    #[ORM\Column(nullable: true, options: ['unsigned' => true])]
    private ?int $rating = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $reply = null;

    #[ORM\Column(enumType: ReviewStatus::class)]
    private ReviewStatus $status = ReviewStatus::Requested;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $replyAt = null;

    public function __construct(ClothesVariant $product, Orders $order, Customers $customer, ?\DateTimeImmutable $now = null)
    {
        $now ??= new \DateTimeImmutable();
        $this->reviewUuid = self::uuidV4();
        $this->product = $product;
        $this->order = $order;
        $this->customer = $customer;
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->requestedAt = $now;
    }

    public function getId(): ?int { return $this->id; }
    public function getReviewUuid(): string { return $this->reviewUuid; }
    public function getProduct(): ClothesVariant { return $this->product; }
    public function getOrder(): Orders { return $this->order; }
    public function getCustomer(): Customers { return $this->customer; }
    public function getRating(): ?int { return $this->rating; }
    public function getComment(): ?string { return $this->comment; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getRequestedAt(): \DateTimeImmutable { return $this->requestedAt; }
    public function getReply(): ?string { return $this->reply; }
    public function getStatus(): ReviewStatus { return $this->status; }
    public function getReplyAt(): ?\DateTimeImmutable { return $this->replyAt; }

    public function submit(int $rating, string $comment, ?\DateTimeImmutable $now = null): void
    {
        if ($this->status !== ReviewStatus::Requested) {
            throw new \DomainException('Cet avis a déjà été envoyé.');
        }
        if ($rating < 1 || $rating > 5) {
            throw new \InvalidArgumentException('La note doit être comprise entre 1 et 5.');
        }
        $comment = trim($comment);
        if ($comment === '' || mb_strlen($comment) > 200) {
            throw new \InvalidArgumentException('Le commentaire doit contenir entre 1 et 200 caractères.');
        }
        $this->rating = $rating;
        $this->comment = $comment;
        $this->status = ReviewStatus::Pending;
        $this->updatedAt = $now ?? new \DateTimeImmutable();
    }

    public function accept(?\DateTimeImmutable $now = null): void
    {
        $this->moderate(ReviewStatus::Accepted, $now);
    }

    public function reject(?\DateTimeImmutable $now = null): void
    {
        $this->moderate(ReviewStatus::Rejected, $now);
    }

    public function updateReply(string $reply, ?\DateTimeImmutable $now = null): void
    {
        if (!in_array($this->status, [ReviewStatus::Accepted, ReviewStatus::Rejected], true)) {
            throw new \DomainException('Seul un avis déjà modéré peut recevoir une réponse.');
        }

        $reply = trim($reply);
        if ($reply === '' || mb_strlen($reply) > 200) {
            throw new \InvalidArgumentException('La réponse doit contenir entre 1 et 200 caractères.');
        }

        $this->reply = $reply;
        $this->replyAt = $now ?? new \DateTimeImmutable();
        $this->updatedAt = $this->replyAt;
    }

    private function moderate(ReviewStatus $status, ?\DateTimeImmutable $now): void
    {
        if ($this->status === ReviewStatus::Requested) {
            throw new \DomainException('Un avis sans note ne peut pas être modéré.');
        }
        $this->status = $status;
        $this->updatedAt = $now ?? new \DateTimeImmutable();
    }

    private static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
