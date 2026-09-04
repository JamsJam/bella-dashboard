<?php

namespace App\Application\Avatar\Dto;

final readonly class AvatarColorTypeDefinitionDto
{
    /**
     * @param class-string $entityClass
     * @param list<string> $associationMethods
     */
    public function __construct(
        public string $type,
        public string $label,
        public string $entityClass,
        public array $associationMethods,
    ) {
    }
}
