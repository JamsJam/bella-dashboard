<?php

namespace App\Application\Config\Dto\Page\Categories\Section;

final class SeoDto
{
    public function __construct(
        public string $title = '',
        public string $description = '',
        public string $keywords = '',
        public string $ogTitle = '',
        public string $ogDescription = '',
        public string $ogUrl = '',
        public string $ogImage = '',
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(...array_map(
            static fn (string $key): string => (string) ($data[$key] ?? ''),
            ['title', 'description', 'keywords', 'ogTitle', 'ogDescription', 'ogUrl', 'ogImage'],
        ));
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
