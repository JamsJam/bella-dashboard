<?php

namespace App\Application\Config\Provider;

use App\Application\Config\Dto\Page\Homepage\HomepageConfigDto;
use App\Application\Page\Service\PageContentCache;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;

final readonly class HomepageConfigProvider
{
    public function __construct(
        private PageContentCache $pageContentCache,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function get(): HomepageConfigDto
    {
        return HomepageConfigDto::fromArray(
            $this->pageContentCache->load('homepage', $this->path()),
        );
    }

    public function save(HomepageConfigDto $config): void
    {
        $data = $config->toArray();
        $data['last-modifyed'] = (new \DateTimeImmutable())->format(DATE_ATOM);
        $yaml = Yaml::dump($data, 10, 2);

        if (file_put_contents($this->path(), $yaml, LOCK_EX) === false) {
            throw new \RuntimeException('Impossible d’enregistrer la configuration de la page d’accueil.');
        }

        $this->pageContentCache->invalidate('homepage');
    }

    private function path(): string
    {
        return $this->projectDir.'/pages/api/homepage.yaml';
    }
}
