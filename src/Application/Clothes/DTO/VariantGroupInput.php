<?php

namespace App\Application\Clothes\DTO;

use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class VariantGroupInput
{
    public ?Clothescolor $color = null;

    #[Assert\Length(max: 50)]
    public ?string $newColorName = null;

    #[Assert\Regex(pattern: '/^#[0-9a-fA-F]{6}$/', message: 'Saisissez une couleur hexadécimale valide.')]
    public ?string $newColorHex = '#000000';

    /** @var list<Clothessize> */
    #[Assert\Count(min: 1, minMessage: 'Sélectionnez au moins une taille.')]
    public array $sizes = [];

    public ?string $description = null;

    #[Assert\Length(max: 200)]
    public ?string $metaDescription = null;

    /** @var list<UploadedFile> */
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins une image.')]
    #[Assert\All([new Assert\Image(mimeTypes: ['image/jpeg', 'image/png'])])]
    public array $images = [];

    #[Assert\Callback]
    public function validateColor(ExecutionContextInterface $context): void
    {
        if ($this->color === null && trim((string) $this->newColorName) === '') {
            $context->buildViolation('Sélectionnez ou créez une couleur.')->atPath('color')->addViolation();
        }
    }
}
