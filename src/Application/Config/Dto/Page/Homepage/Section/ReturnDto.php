<?php

namespace App\Application\Config\Dto\Page\Homepage\Section;

use App\Application\Config\Dto\Page\Homepage\Item\ReturnStepDto;

final class ReturnDto
{
    public function __construct(public array $steps = [])
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(array_values(array_map(
            static fn (array $step): ReturnStepDto => ReturnStepDto::fromArray($step),
            array_filter($data['steps'] ?? [], 'is_array'),
        )));
    }

    public function toArray(): array
    {
        return ['steps' => array_map(static fn (ReturnStepDto $step): array => $step->toArray(), $this->steps)];
    }
}
