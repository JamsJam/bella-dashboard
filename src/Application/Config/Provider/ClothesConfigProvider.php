<?php

namespace App\Application\Config\Provider;

use App\Application\Config\Dto\ClothesConfigDto;

final readonly class ClothesConfigProvider extends AbstractConfigProvider
{
    private const FILE_NAME = 'clothes';

    public function get(): ClothesConfigDto
    {
        return ClothesConfigDto::fromArray($this->read(self::FILE_NAME));
    }

    public function save(ClothesConfigDto $config): void
    {
        $this->write(self::FILE_NAME, $config->toArray());
    }
}
