<?php

namespace App\Application\Config\Service;

use App\Application\Config\Dto\PageConfigDto;
use App\Application\Config\Dto\PageSectionDto;
use App\Application\Config\Provider\PageConfigProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class PageConfigService
{
    public function __construct(
        private PageConfigProvider $provider,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function get(?string $slug = null): PageConfigDto
    {
        return $this->provider->get($slug);
    }

    /**
     * @param array<int, UploadedFile> $sectionImageFiles
     */
    public function save(PageConfigDto $config, array $sectionImageFiles = []): void
    {
        foreach ($config->sections as $index => $section) {
            if (!$section instanceof PageSectionDto || !isset($sectionImageFiles[$index])) {
                continue;
            }

            $section->image = $this->storeSectionImage($sectionImageFiles[$index], $config, $section);
        }

        $this->provider->save($config);
    }

    /**
     * @return list<PageConfigDto>
     */
    public function all(): array
    {
        return $this->provider->all();
    }

    private function storeSectionImage(UploadedFile $file, PageConfigDto $config, PageSectionDto $section): string
    {
        $publicPath = '/images/upload/config/pages';
        $directory = $this->projectDir . '/public' . $publicPath;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create upload directory "%s".', $directory));
        }

        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        if ('jpeg' === $extension) {
            $extension = 'jpg';
        }

        $sectionName = '' !== $section->type ? $section->type : 'section';
        $slugger = new AsciiSlugger();
        $slug = strtolower((string) $slugger->slug($config->normalizedSlug() . '-' . $sectionName));
        $filename = sprintf('page-%s-%s.%s', $slug, bin2hex(random_bytes(4)), $extension);

        $file->move($directory, $filename);

        return $publicPath . '/' . $filename;
    }
}
