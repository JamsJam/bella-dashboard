<?php

namespace App\Application\Avatar\Services;

use App\Application\Avatar\Dto\Input\AvatarRenameBatchInputDto;

final readonly class AvatarRenameBatchInputService
{
    public function __construct(
        private AvatarInputValidator $validator,
    ) {
    }

    public function prepare(string $renamesJson): AvatarRenameBatchInputDto
    {
        $input = new AvatarRenameBatchInputDto(
            renames: json_decode($renamesJson, true),
        );

        $this->validator->validate($input);

        return $input;
    }
}
