<?php

namespace App\Application\Config\Provider;

use App\Application\Config\Dto\Page\Categories\CategoriesConfigDto;
use App\Application\Page\Service\PageContentCache;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

final readonly class CategoriesConfigProvider
{
    public function __construct(
        private PageContentCache $pageContentCache,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function get(): CategoriesConfigDto
    {
        return CategoriesConfigDto::fromArray(
            $this->pageContentCache->load('categories', $this->path()),
        );
    }

    public function save(CategoriesConfigDto $config): void
    {
        $data = $config->toArray();
        $data['last-modifyed'] = (new \DateTimeImmutable())->format(DATE_ATOM);

        if (false === file_put_contents($this->path(), Yaml::dump($data, 10, 2), LOCK_EX)) {
            throw new \RuntimeException('Impossible d’enregistrer la configuration de la page des catégories.');
        }

        $this->pageContentCache->invalidate('categories');
    }

    private function path(): string
    {
        return $this->projectDir . '/pages/api/categories.yaml';
    }
}
