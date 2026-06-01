<?php

namespace App\Application\Config\Provider;

use App\Application\Config\ConfigModel\GeneralConfigModel;
use App\Application\Config\ConfigModel\OrderConfigModel;
use App\Application\Config\Service\ConfigurationYamlService;

final readonly class ApplicationConfigProvider
{
    public function __construct(
        private ConfigurationYamlService $configurationYamlService,
    ) {
    }

    public function getGeneralConfig(): GeneralConfigModel
    {
        return GeneralConfigModel::fromArray(
            $this->configurationYamlService->getSection('general', 'general'),
        );
    }

    public function getOrderConfig(): OrderConfigModel
    {
        return OrderConfigModel::fromArray(
            $this->configurationYamlService->getSection('order', 'order'),
        );
    }

    /**
     * @return array{general: array<string, mixed>, order: array<string, mixed>}
     */
    public function getAll(): array
    {
        return [
            'general' => $this->getGeneralConfig()->toArray(),
            'order' => $this->getOrderConfig()->toArray(),
        ];
    }
}
