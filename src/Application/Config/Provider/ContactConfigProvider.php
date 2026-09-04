<?php

namespace App\Application\Config\Provider;

use App\Application\Config\Dto\ContactConfigDto;

final readonly class ContactConfigProvider extends AbstractConfigProvider
{
    private const FILE_NAME = 'contact';

    public function get(): ContactConfigDto
    {
        return ContactConfigDto::fromArray($this->read(self::FILE_NAME));
    }

    public function save(ContactConfigDto $config): void
    {
        $this->write(self::FILE_NAME, $config->toArray());
    }

    public function exists(): bool
    {
        return $this->fileExists(self::FILE_NAME);
    }
}
