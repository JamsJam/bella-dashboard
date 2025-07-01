<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[UniqueEntity(
    fields: 'slug',
    message: 'Ce slug correspond a un projet existant'
)]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $editedAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(groups: ['generalProject'])]
    #[Assert\Type(
        'string',
        groups: ['generalProject']
    )]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        groups: ['generalProject']
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\NotBlank(groups: ['generalProject'])]
    #[Assert\Type(
        'string',
        groups: ['generalProject']
    )]
    #[Assert\Length(
        min: 50,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.',
        groups: ['generalProject']
    )]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Type('array', groups: ['generalProject'])]
    #[Assert\All(
        new Assert\Type('string')
    )]
    private ?array $images = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(groups: ['generalProject'])]
    private ?string $projectlink = null;

    #[ORM\Column]
    #[Assert\Type('boolean', groups: ['generalProject'])]
    private ?bool $isOnline = null;

    #[ORM\Column]
    #[Assert\Type('boolean', groups: ['generalProject'])]
    private ?bool $isGitpublic = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(groups: ['generalProject'])]
    private ?string $gitlink = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    // #[Assert\Url(groups: ['caseStudy'])]
    private ?string $casestudy = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'project')]
    private Collection $tags;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\Type(
        'string',
        groups: ['generalProject']
    )]
    private ?string $slug = null;

    /**
     * @var Collection<int, Technology>
     */
    #[ORM\ManyToMany(targetEntity: Technology::class, inversedBy: 'projects')]
    private Collection $technology;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->technology = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getEditedAt(): ?\DateTimeImmutable
    {
        return $this->editedAt;
    }

    public function setEditedAt(\DateTimeImmutable $editedAt): static
    {
        $this->editedAt = $editedAt;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getProjectlink(): ?string
    {
        return $this->projectlink;
    }

    public function setProjectlink(?string $projectlink): static
    {
        $this->projectlink = $projectlink;

        return $this;
    }

    public function isOnline(): ?bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;

        return $this;
    }

    public function isGitpublic(): ?bool
    {
        return $this->isGitpublic;
    }

    public function setIsGitpublic(bool $isGitpublic): static
    {
        $this->isGitpublic = $isGitpublic;

        return $this;
    }

    public function getGitlink(): ?string
    {
        return $this->gitlink;
    }

    public function setGitlink(?string $gitlink): static
    {
        $this->gitlink = $gitlink;

        return $this;
    }

    public function getCasestudy(): ?string
    {
        return $this->casestudy;
    }

    public function setCasestudy(?string $casestudy): static
    {
        $this->casestudy = $casestudy;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addProject($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeProject($this);
        }

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

    /**
     * @return Collection<int, Technology>
     */
    public function getTechnology(): Collection
    {
        return $this->technology;
    }

    public function addTechnology(Technology $technology): static
    {
        if (!$this->technology->contains($technology)) {
            $this->technology->add($technology);
        }

        return $this;
    }

    public function removeTechnology(Technology $technology): static
    {
        $this->technology->removeElement($technology);

        return $this;
    }
}
