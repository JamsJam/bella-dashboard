<?php

namespace App\Application\Config\Service;

use App\Application\Config\Dto\ContactConfigDto;
use App\Application\Config\Provider\ContactConfigProvider;

final readonly class ContactConfigService
{
    public function __construct(
        private ContactConfigProvider $provider,
    ) {
    }

    public function get(): ContactConfigDto
    {
        return $this->provider->get();
    }

    public function save(ContactConfigDto $config): void
    {
        $this->provider->save($config);
    }

    public function exists(): bool
    {
        return $this->provider->exists();
    }
}
