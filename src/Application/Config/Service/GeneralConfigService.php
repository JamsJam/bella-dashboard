<?php

namespace App\Application\Config\Service;

use App\Application\Config\Dto\GeneralConfigDto;
use App\Application\Config\Provider\GeneralConfigProvider;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

final readonly class GeneralConfigService
{
    public function __construct(
        private GeneralConfigProvider $provider,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    public function get(): GeneralConfigDto
    {
        return $this->provider->get();
    }

    public function save(GeneralConfigDto $config, ?UploadedFile $logoFile = null, ?UploadedFile $faviconFile = null): void
    {
        if ($logoFile instanceof UploadedFile) {
            $config->siteLogo = $this->storeImage($logoFile, 'logo');
        }

        if ($faviconFile instanceof UploadedFile) {
            $config->favicon = $this->storeImage($faviconFile, 'favicon');
        }

        $this->provider->save($config);
    }

    private function storeImage(UploadedFile $file, string $prefix): string
    {
        $publicPath = '/images/upload/config';
        $directory = $this->projectDir . '/public' . $publicPath;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create upload directory "%s".', $directory));
        }

        $extension = strtolower((string) ($file->guessExtension() ?: $file->getClientOriginalExtension()));
        if ('jpeg' === $extension) {
            $extension = 'jpg';
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = strtolower((string) (new AsciiSlugger())->slug($originalName ?: $prefix));
        $filename = sprintf('%s-%s-%s.%s', $prefix, $slug, bin2hex(random_bytes(4)), $extension);

        $file->move($directory, $filename);

        return $publicPath . '/' . $filename;
    }
}
