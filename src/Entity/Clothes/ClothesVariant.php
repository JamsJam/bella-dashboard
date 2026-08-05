<?php

namespace App\Entity\Clothes;

use App\Enum\ClotheStatus;
use App\Entity\Avatar\Body\Body;
use App\Entity\SizeGuide;
use App\Entity\Traits\DateFieldsTrait;
use App\Repository\Clothes\ClothesVariantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClothesVariantRepository::class)]
#[ORM\Table(name: 'clothes_variant')]
#[ORM\Index(name: 'IDX_CLOTHES_VARIANT_SLUG', columns: ['slug'])]
#[ORM\Index(name: 'IDX_CLOTHES_VARIANT_PUBLICATION_STATUS', fields: ['publicationStatus'])]
#[ORM\UniqueConstraint(name: 'UNIQ_CLOTHES_VARIANT_NAME', columns: ['name'])]
#[ORM\UniqueConstraint(name: 'UNIQ_CLOTHES_VARIANT_SKU', columns: ['sku'])]
class ClothesVariant
{
    use DateFieldsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Clothes $clothes = null;

    #[ORM\Column(length: 70)]
    private ?string $name = null;

    #[ORM\Column(length: 70)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?array $images = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $highlightImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $bestsellerImage = null;

    #[ORM\ManyToOne(inversedBy: 'variants')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?SizeGuide $sizeGuide = null;

    #[ORM\ManyToOne(inversedBy: 'variants', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Clothescolor $color = null;

    #[ORM\ManyToOne(inversedBy: 'variants', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Clothessize $size = null;

    #[ORM\Column(length: 100)]
    private ?string $sku = null;

    #[ORM\Column(length: 200, nullable: true)]
    private ?string $metadescription = null;

    #[ORM\Column(options: ['unsigned' => true])]
    private int $stock = 0;

    #[ORM\Column]
    private bool $isBestseller = false;

    #[ORM\Column]
    private bool $isInCarousel = false;

    #[ORM\Column(enumType: ClotheStatus::class)]
    private ClotheStatus $publicationStatus = ClotheStatus::Draft;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scheduledPublicationAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $archivedAt = null;

    /**
     * @var Collection<int, Body>
     */
    #[ORM\ManyToMany(targetEntity: Body::class, inversedBy: 'clothesVariants')]
    #[ORM\JoinTable(name: 'body_clothes_variant')]
    private Collection $bodies;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->setCreatedAt($now);
        $this->setEditedAt($now);
        $this->bodies = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClothes(): ?Clothes
    {
        return $this->clothes;
    }

    public function setClothes(?Clothes $clothes): static
    {
        $this->clothes = $clothes;

        return $this;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }

    public function setImages(?array $images): static
    {
        $this->images = $images;

        return $this;
    }

    public function getHighlightImage(): ?string
    {
        return $this->highlightImage;
    }

    public function setHighlightImage(?string $highlightImage): static
    {
        $this->highlightImage = $highlightImage;

        return $this;
    }

    public function getBestsellerImage(): ?string
    {
        return $this->bestsellerImage;
    }

    public function setBestsellerImage(?string $bestsellerImage): static
    {
        $this->bestsellerImage = $bestsellerImage;

        return $this;
    }

    public function getSizeGuide(): ?SizeGuide
    {
        return $this->sizeGuide;
    }

    public function setSizeGuide(?SizeGuide $sizeGuide): static
    {
        $this->sizeGuide = $sizeGuide;

        return $this;
    }

    public function getColor(): ?Clothescolor
    {
        return $this->color;
    }

    public function setColor(?Clothescolor $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getSize(): ?Clothessize
    {
        return $this->size;
    }

    public function setSize(?Clothessize $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getMetadescription(): ?string
    {
        return $this->metadescription;
    }

    public function setMetadescription(?string $metadescription): static
    {
        $this->metadescription = $metadescription;

        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = max(0, $stock);

        return $this;
    }

    public function isBestseller(): bool
    {
        return $this->isBestseller;
    }

    public function setIsBestseller(bool $isBestseller): static
    {
        $this->isBestseller = $isBestseller;

        return $this;
    }

    public function isInCarousel(): bool
    {
        return $this->isInCarousel;
    }

    public function setIsInCarousel(bool $isInCarousel): static
    {
        $this->isInCarousel = $isInCarousel;

        return $this;
    }

    public function getPublicationStatus(): ClotheStatus
    {
        return $this->publicationStatus;
    }

    public function setPublicationStatus(ClotheStatus $publicationStatus): static
    {
        $this->publicationStatus = $publicationStatus;

        return $this;
    }

    public function getScheduledPublicationAt(): ?\DateTimeImmutable
    {
        return $this->scheduledPublicationAt;
    }

    public function setScheduledPublicationAt(?\DateTimeImmutable $scheduledPublicationAt): static
    {
        $this->scheduledPublicationAt = $scheduledPublicationAt;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): static
    {
        $this->archivedAt = $archivedAt;

        return $this;
    }

    public function getDisplayName(): string
    {
        return trim(sprintf(
            '%s %s',
            (string) $this->name,
            (string) $this->color?->getName(),
        ));
    }

    /**
     * @return Collection<int, Body>
     */
    public function getBodies(): Collection
    {
        return $this->bodies;
    }

    public function addBody(Body $body): static
    {
        if (!$this->bodies->contains($body)) {
            $this->bodies->add($body);
            $body->addClothesVariant($this);
        }

        return $this;
    }

    public function removeBody(Body $body): static
    {
        if ($this->bodies->removeElement($body)) {
            $body->removeClothesVariant($this);
        }

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->publicationStatus === ClotheStatus::Online
            && $this->stock > 0;
    }
}
