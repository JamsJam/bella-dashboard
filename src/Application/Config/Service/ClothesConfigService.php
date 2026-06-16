<?php

namespace App\Application\Config\Service;

use App\Application\Config\Dto\ClothesConfigDto;
use App\Application\Config\Provider\ClothesConfigProvider;

final readonly class ClothesConfigService
{
    public function __construct(
        private ClothesConfigProvider $provider,
    ) {
    }

    public function get(): ClothesConfigDto
    {
        return $this->provider->get();
    }

    public function save(ClothesConfigDto $config): void
    {
        $this->provider->save($config);
    }
}
