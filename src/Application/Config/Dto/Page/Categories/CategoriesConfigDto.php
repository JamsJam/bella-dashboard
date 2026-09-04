<?php

namespace App\Application\Config\Dto\Page\Categories;

use App\Application\Config\Dto\Page\Categories\Section\BandeauDto;
use App\Application\Config\Dto\Page\Categories\Section\SeoDto;

final class CategoriesConfigDto
{
    public function __construct(
        public SeoDto $seo = new SeoDto(),
        public BandeauDto $bandeau = new BandeauDto(),
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            SeoDto::fromArray(is_array($data['seo'] ?? null) ? $data['seo'] : []),
            BandeauDto::fromArray(is_array($data['bandeau'] ?? null) ? $data['bandeau'] : []),
        );
    }

    public function toArray(): array
    {
        return [
            'seo' => $this->seo->toArray(),
            'bandeau' => $this->bandeau->toArray(),
        ];
    }
}
