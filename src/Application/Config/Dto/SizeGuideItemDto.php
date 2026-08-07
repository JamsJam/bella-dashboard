<?php

namespace App\Application\Config\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class SizeGuideItemDto
{
    public function __construct(
        public ?string $uuid = null,
        #[Assert\NotBlank(message: 'Le nom de la mesure est obligatoire.')]
        #[Assert\Length(max: 120, maxMessage: 'Le nom ne doit pas dépasser 120 caractères.')]
        public string $label = '',
        public ?string $description = null,
        public int $measurementCount = 0,
    ) {
    }
}
