<?php

namespace App\Application\Config\Provider;

use App\Service\YamlLoaderService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

abstract readonly class AbstractConfigProvider
{
    private const CACHE_PREFIX = 'application_config.';

    public function __construct(
        private YamlLoaderService $yamlLoaderService,
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    protected function read(string $fileName): array
    {
        $cacheKey = $this->cacheKey($fileName);
        $item = $this->cache->getItem($cacheKey);
        $path = $this->path($fileName);
        $modifiedAt = is_file($path) ? filemtime($path) : null;

        if ($item->isHit()) {
            $cached = $item->get();

            if (
                is_array($cached)
                && ($cached['_config_mtime'] ?? null) === $modifiedAt
                && isset($cached['data'])
                && is_array($cached['data'])
            ) {
                return $cached['data'];
            }
        }

        try {
            $data = $this->yamlLoaderService->load($path);
        } catch (FileNotFoundException) {
            $data = [];
        } catch (\RuntimeException) {
            $data = [];
        }

        $data = is_array($data) ? $data : [];
        $item->set([
            '_config_mtime' => $modifiedAt,
            'data' => $data,
        ]);
        $this->cache->save($item);

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function write(string $fileName, array $data): void
    {
        $directory = $this->configDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create configuration directory "%s".', $directory));
        }

        $path = $this->path($fileName);
        $yaml = Yaml::dump($data, 8, 4);

        if (file_put_contents($path, $yaml) === false) {
            throw new \RuntimeException(sprintf('Unable to write configuration file "%s".', $path));
        }

        $this->cache->deleteItem($this->cacheKey($fileName));
    }

    protected function normalizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?: 'home';
        $slug = trim($slug, '-_');

        return $slug !== '' ? $slug : 'home';
    }

    protected function fileExists(string $fileName): bool
    {
        return is_file($this->path($fileName));
    }

    /**
     * @return list<string>
     */
    protected function listFiles(string $prefix): array
    {
        $files = glob($this->configDirectory().'/'.$prefix.'*.yaml') ?: [];

        return array_values(array_map(
            static fn (string $path): string => basename($path, '.yaml'),
            $files,
        ));
    }

    private function path(string $fileName): string
    {
        return $this->configDirectory().'/'.$fileName.'.yaml';
    }

    private function configDirectory(): string
    {
        return $this->projectDir.'/var/config';
    }

    private function cacheKey(string $fileName): string
    {
        return self::CACHE_PREFIX.str_replace(['/', '\\', '.'], '_', $fileName);
    }
}
