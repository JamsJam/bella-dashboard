<?php

namespace App\Application\Config\Provider;

use App\Application\Config\Dto\PageConfigDto;

final readonly class PageConfigProvider extends AbstractConfigProvider
{
    private const FILE_NAME = 'page';

    public function get(?string $slug = null): PageConfigDto
    {
        $normalizedSlug = $this->normalizeSlug($slug ?? 'home');

        $pages = $this->read(self::FILE_NAME);
        $page = $pages[$normalizedSlug] ?? null;

        if (!is_array($page)) {
            $page = $this->read($this->legacyFileName($normalizedSlug));
        }

        return PageConfigDto::fromArray(is_array($page) ? $page : [], $normalizedSlug);
    }

    public function save(PageConfigDto $config): void
    {
        $pages = $this->read(self::FILE_NAME);
        $pages = is_array($pages) ? $pages : [];
        $pages[$config->normalizedSlug()] = $config->toArray();

        $this->write(self::FILE_NAME, $pages);
    }

    /**
     * @return list<PageConfigDto>
     */
    public function all(): array
    {
        $slugs = ['home'];

        foreach ($this->read(self::FILE_NAME) as $slug => $page) {
            if (is_string($slug) && is_array($page)) {
                $slugs[] = $this->normalizeSlug($slug);
            }
        }

        foreach ($this->listFiles('page_') as $fileName) {
            $slug = substr($fileName, strlen('page_'));
            if ($slug !== '') {
                $slugs[] = $this->normalizeSlug($slug);
            }
        }

        $slugs = array_values(array_unique($slugs));
        sort($slugs);

        return array_map(fn (string $slug): PageConfigDto => $this->get($slug), $slugs);
    }

    private function legacyFileName(string $slug): string
    {
        return 'page_'.$slug;
    }
}
