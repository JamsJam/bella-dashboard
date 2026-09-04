<?php

namespace App\Application\Config\Provider;

use App\Application\Config\Dto\GeneralConfigDto;

final readonly class GeneralConfigProvider extends AbstractConfigProvider
{
    private const FILE_NAME = 'general';

    public function get(): GeneralConfigDto
    {
        return GeneralConfigDto::fromArray($this->read(self::FILE_NAME));
    }

    public function save(GeneralConfigDto $config): void
    {
        $this->write(self::FILE_NAME, $config->toArray());
    }
}
