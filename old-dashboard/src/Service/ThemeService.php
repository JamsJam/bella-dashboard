<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

final class ThemeService
{
    public function __construct(
        private RequestStack $requestStack,
        private $configPath = __DIR__.'/../Config/config.yaml',
    ) {
    }

    public function getTheme(): ?string
    {
        $session = $this->requestStack->getSession();

        if (!$session->get('theme')) {
            if (!file_exists($this->configPath)) {
                return null;
            }

            $config = Yaml::parseFile($this->configPath);

            $defaultTheme = $config['parameters']['theme'];
            $session->set('theme', $defaultTheme);
        }
        $theme = $session->get('theme');


        return $theme ?? null;
    }

    public function setTheme(string $theme): bool
    {
        $session = $this->requestStack->getSession();

        $session->set('theme', $theme);

        return (bool) true;
    }
}
