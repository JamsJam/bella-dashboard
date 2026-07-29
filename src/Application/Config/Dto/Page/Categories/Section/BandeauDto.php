<?php

namespace App\Application\Config\Dto\Page\Categories\Section;

final class BandeauDto
{
    public function __construct(
        public ?string $title = null,
        public ?string $cta = null,
        public ?string $background = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            self::nullableString($data['title'] ?? null),
            self::nullableString($data['cta'] ?? null),
            self::nullableString($data['background'] ?? null),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
