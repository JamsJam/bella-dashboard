<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Dto\Input\AvatarRenameValidationInputDto;

final readonly class AvatarRenameValidationInputService
{
    public function __construct(
        private AvatarInputValidator $validator,
    ) {
    }

    public function prepare(
        string $name,
        string $category,
        string $filtersJson,
        mixed $authorization = false,
    ): AvatarRenameValidationInputDto {
        $input = new AvatarRenameValidationInputDto(
            name: $name,
            category: $category,
            filters: json_decode($filtersJson, true),
            authorization: filter_var($authorization, FILTER_VALIDATE_BOOL),
        );

        $this->validator->validate($input);

        return $input;
    }
}
