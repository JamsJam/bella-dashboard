<?php

namespace App\Application\Page\Service;

use App\Service\YamlLoaderService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PageContentCache
{
    private const CACHE_PREFIX = 'api_page.';

    public function __construct(
        private YamlLoaderService $yamlLoader,
        #[Autowire(service: 'cache.app')]
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function load(string $page, string $path): array
    {
        $item = $this->cache->getItem($this->key($page));
        if ($item->isHit() && is_array($item->get())) {
            return $item->get();
        }

        $data = $this->yamlLoader->load($path);
        $item->set($data);
        $this->cache->save($item);

        return $data;
    }

    public function invalidate(string $page): void
    {
        $this->cache->deleteItem($this->key($page));
    }

    private function key(string $page): string
    {
        return self::CACHE_PREFIX.$page;
    }
}
