<?php

namespace App\UI\Toggle;

final readonly class ToggleModel
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public string $id,
        public string $label,
        public bool $checked = false,
        public ?string $name = null,
        public bool $disabled = false,
        public string $eventName = 'toggle:change',
        public array $payload = [],
    ) {
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     label: string,
     *     checked: bool,
     *     disabled: bool,
     *     eventName: string,
     *     payload: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? $this->id,
            'label' => $this->label,
            'checked' => $this->checked,
            'disabled' => $this->disabled,
            'eventName' => $this->eventName,
            'payload' => $this->payload,
        ];
    }
}
