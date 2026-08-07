<?php

namespace App\UI\Toggle;

final readonly class ToggleActionModel
{
    public function __construct(
        public string $url,
        public string $method = 'POST',
        public ?string $csrfToken = null,
        public ?string $label = null,
    ) {
    }

    /**
     * @return array{url: string, method: string, csrfToken?: string, label?: string}
     */
    public function toArray(): array
    {
        $action = [
            'url' => $this->url,
            'method' => $this->method,
        ];

        if (null !== $this->csrfToken) {
            $action['csrfToken'] = $this->csrfToken;
        }

        if (null !== $this->label) {
            $action['label'] = $this->label;
        }

        return $action;
    }
}
