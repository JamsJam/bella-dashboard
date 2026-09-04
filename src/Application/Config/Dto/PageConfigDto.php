<?php

namespace App\Application\Config\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final class PageConfigDto
{
    public function __construct(
        public string $slug = 'home',
        public string $seoTitle = '',
        public string $seoMetadescription = '',
        public string $openGraphYaml = '',
        public array $sections = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, string $slug = 'home'): self
    {
        $seo = $data['seo'] ?? [];
        $seo = is_array($seo) ? $seo : [];
        $page = $data['page'] ?? [];
        $page = is_array($page) ? $page : [];
        $sections = [];
        foreach (($page['sections'] ?? $data['sections'] ?? []) as $section) {
            if (is_array($section)) {
                $sections[] = PageSectionDto::fromArray($section);
            }
        }

        return new self(
            slug: trim((string) ($data['slug'] ?? $slug)) ?: 'home',
            seoTitle: trim((string) ($seo['title'] ?? '')),
            seoMetadescription: trim((string) ($seo['metadescription'] ?? $seo['description'] ?? '')),
            openGraphYaml: Yaml::dump(is_array($seo['openGraph'] ?? $seo['open_graph'] ?? null) ? ($seo['openGraph'] ?? $seo['open_graph']) : [], 8, 2),
            sections: $sections,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->normalizedSlug(),
            'seo' => [
                'title' => $this->seoTitle,
                'metadescription' => $this->seoMetadescription,
                'openGraph' => $this->parseYamlBlock($this->openGraphYaml, []),
            ],
            'page' => [
                'sections' => array_values(array_filter(
                    array_map(
                        static fn (PageSectionDto $section): array => $section->toArray(),
                        $this->sections,
                    ),
                    static fn (array $section): bool => '' !== $section['type'] || [] !== $section['content'],
                )),
            ],
        ];
    }

    public function normalizedSlug(): string
    {
        $slug = strtolower(trim($this->slug));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug) ?: 'home';
        $slug = trim($slug, '-_');

        return '' !== $slug ? $slug : 'home';
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateYamlBlock($this->openGraphYaml, 'openGraphYaml', $context);
    }

    private function parseYamlBlock(string $yaml, mixed $fallback): mixed
    {
        if ('' === trim($yaml)) {
            return $fallback;
        }

        try {
            $parsed = Yaml::parse($yaml);
        } catch (ParseException) {
            return $fallback;
        }

        return $parsed ?? $fallback;
    }

    private function validateYamlBlock(string $yaml, string $propertyPath, ExecutionContextInterface $context): void
    {
        if ('' === trim($yaml)) {
            return;
        }

        try {
            Yaml::parse($yaml);
        } catch (ParseException $exception) {
            $context
                ->buildViolation($exception->getMessage())
                ->atPath($propertyPath)
                ->addViolation();
        }
    }
}
