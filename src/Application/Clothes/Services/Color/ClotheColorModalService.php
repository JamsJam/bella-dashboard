<?php

namespace App\Application\Clothes\Services\Color;

use App\Application\Clothes\DTO\ClotheColorModalDto;
use App\Application\Clothes\Mapper\ClotheColorMapper;
use App\Application\Clothes\Provider\ClotheColorProvider;

final readonly class ClotheColorModalService
{
    public function __construct(
        private ClotheColorProvider $provider,
        private ClotheColorMapper $mapper,
    ) {
    }

    public function getModal(): ClotheColorModalDto
    {
        return new ClotheColorModalDto(
            colors: array_map($this->mapper->map(...), $this->provider->findAllByName()),
        );
    }
}
