<?php

namespace App\Application\Config\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class ConfigurationYamlService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getSection(string $fileName, string $section): array
    {
        $config = $this->parse($fileName);
        $sectionConfig = $config[$section] ?? [];

        return is_array($sectionConfig) ? $sectionConfig : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function parse(string $fileName): array
    {
        $path = $this->resolvePath($fileName);

        if (!is_file($path)) {
            return [];
        }

        try {
            $config = Yaml::parseFile($path);
        } catch (ParseException) {
            return [];
        }

        return is_array($config) ? $config : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveSection(string $fileName, string $section, array $data): void
    {
        $path = $this->resolvePath($fileName);
        $config = $this->parse($fileName);
        $config[$section] = $data;

        $yaml = Yaml::dump($config, 4, 4);

        if (file_put_contents($path, $yaml) === false) {
            throw new \RuntimeException(sprintf('Unable to write configuration file "%s".', $path));
        }
    }

    private function resolvePath(string $fileName): string
    {
        $normalizedFileName = trim($fileName);
        if (!str_ends_with($normalizedFileName, '.yaml')) {
            $normalizedFileName .= '.yaml';
        }

        return $this->projectDir.'/src/Application/Config/Config/'.$normalizedFileName;
    }
}
