<?php

namespace App\Application\Config\Dto;

final class PageSectionDto
{
    public const CONTENT_TYPE_TEXT = 'text';
    public const CONTENT_TYPE_LIST = 'list';
    public const CONTENT_TYPE_IMAGE = 'image';
    public const CONTENT_TYPE_BESTSELLER = 'bestseller';

    /**
     * @param array<int, PageSectionContentItemDto> $listItems
     */
    public function __construct(
        public string $type = '',
        public string $contentType = self::CONTENT_TYPE_TEXT,
        public string $text = '',
        public string $image = '',
        public array $listItems = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $content = $data['content'] ?? [];
        $content = is_array($content) ? $content : [];
        $contentType = trim((string) ($content['type'] ?? self::CONTENT_TYPE_TEXT));
        $listItems = [];

        foreach (($content['items'] ?? []) as $item) {
            if (is_array($item)) {
                $listItems[] = PageSectionContentItemDto::fromArray($item);
            }
        }

        return new self(
            type: trim((string) ($data['type'] ?? '')),
            contentType: in_array($contentType, self::contentTypes(), true) ? $contentType : self::CONTENT_TYPE_TEXT,
            text: trim((string) ($content['text'] ?? '')),
            image: trim((string) ($content['image'] ?? '')),
            listItems: $listItems,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'content' => $this->contentToArray(),
        ];
    }

    /**
     * @return list<string>
     */
    public static function contentTypes(): array
    {
        return [
            self::CONTENT_TYPE_TEXT,
            self::CONTENT_TYPE_LIST,
            self::CONTENT_TYPE_IMAGE,
            self::CONTENT_TYPE_BESTSELLER,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contentToArray(): array
    {
        return match ($this->contentType) {
            self::CONTENT_TYPE_LIST => [
                'type' => self::CONTENT_TYPE_LIST,
                'items' => array_values(array_filter(
                    array_map(
                        static fn (PageSectionContentItemDto $item): array => $item->toArray(),
                        $this->listItems,
                    ),
                    static fn (array $item): bool => '' !== $item['value'],
                )),
            ],
            self::CONTENT_TYPE_IMAGE => [
                'type' => self::CONTENT_TYPE_IMAGE,
                'image' => $this->image,
            ],
            self::CONTENT_TYPE_BESTSELLER => [
                'type' => self::CONTENT_TYPE_BESTSELLER,
            ],
            default => [
                'type' => self::CONTENT_TYPE_TEXT,
                'text' => $this->text,
            ],
        };
    }
}
