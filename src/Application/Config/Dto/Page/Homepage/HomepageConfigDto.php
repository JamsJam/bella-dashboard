<?php

namespace App\Application\Config\Dto\Page\Homepage;

use App\Application\Config\Dto\Page\Homepage\Section\AboutDto;
use App\Application\Config\Dto\Page\Homepage\Section\LandingDto;
use App\Application\Config\Dto\Page\Homepage\Section\ManualDto;
use App\Application\Config\Dto\Page\Homepage\Section\ReturnDto;
use App\Application\Config\Dto\Page\Homepage\Section\SeoDto;

final class HomepageConfigDto
{
    public function __construct(
        public LandingDto $landing = new LandingDto(),
        public AboutDto $about = new AboutDto(),
        public ManualDto $manual = new ManualDto(),
        public ReturnDto $return = new ReturnDto(),
        public SeoDto $seo = new SeoDto(),
    ) {
    }

    public static function fromArray(array $data): self
    {
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];

        return new self(
            LandingDto::fromArray(is_array($sections['landing'] ?? null) ? $sections['landing'] : []),
            AboutDto::fromArray(is_array($sections['about'] ?? null) ? $sections['about'] : []),
            ManualDto::fromArray(is_array($sections['manual'] ?? null) ? $sections['manual'] : []),
            ReturnDto::fromArray(is_array($sections['return'] ?? null) ? $sections['return'] : []),
            SeoDto::fromArray(is_array($data['seo'] ?? null) ? $data['seo'] : []),
        );
    }

    public function toArray(): array
    {
        return [
            'sections' => [
                'landing' => $this->landing->toArray(),
                'about' => $this->about->toArray(),
                'manual' => $this->manual->toArray(),
                'return' => $this->return->toArray(),
            ],
            'seo' => $this->seo->toArray(),
        ];
    }
}
