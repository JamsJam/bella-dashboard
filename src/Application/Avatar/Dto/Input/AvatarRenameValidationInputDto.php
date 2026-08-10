<?php

namespace App\Application\Avatar\Dto\Input;

use App\Application\Avatar\Validator\Constraint\ValidAvatarRenameFilters;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidAvatarRenameFilters]
final readonly class AvatarRenameValidationInputDto
{
    /** @param array<string, mixed>|mixed $filters */
    public function __construct(
        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Regex(
            pattern: '/^[A-Za-z0-9_-]+\.png$/',
            message: 'Le nom doit être un nom de fichier PNG valide.',
        )]
        public string $name,
        #[Assert\NotBlank(message: 'La catégorie est obligatoire.')]
        #[Assert\Choice(
            choices: ['body', 'face', 'eyebrows', 'eyes', 'hair', 'mouth', 'nose'],
            message: 'La catégorie sélectionnée est invalide.',
        )]
        public string $category,
        #[Assert\Type(type: 'array', message: 'Les filtres doivent être un tableau.')]
        public mixed $filters,
        public bool $authorization = false,
    ) {
    }
}
