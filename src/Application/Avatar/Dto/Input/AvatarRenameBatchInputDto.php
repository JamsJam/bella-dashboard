<?php

namespace App\Application\Avatar\Dto\Input;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AvatarRenameBatchInputDto
{
    public function __construct(
        #[Assert\Type(type: 'list', message: 'Le lot de renommages doit être une liste.')]
        #[Assert\All(constraints: [
            new Assert\Collection(
                fields: [
                    'avatarTempId' => [
                        new Assert\NotBlank(message: 'L’identifiant temporaire est obligatoire.'),
                        new Assert\Type(type: 'numeric', message: 'L’identifiant temporaire doit être numérique.'),
                        new Assert\Positive(message: 'L’identifiant temporaire doit être positif.'),
                    ],
                ],
                allowExtraFields: true,
            ),
        ])]
        public mixed $renames,
    ) {
    }

    /** @return list<int> */
    public function avatarTempIds(): array
    {
        return array_map(
            static fn (array $rename): int => (int) $rename['avatarTempId'],
            $this->renames,
        );
    }
}
