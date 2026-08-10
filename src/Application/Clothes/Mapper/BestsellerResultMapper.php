<?php

namespace App\Application\Clothes\Mapper;

use App\Application\Clothes\Model\BestsellerUpdateResult;

final readonly class BestsellerResultMapper
{
    public function __construct(
        private ClotheProductGridItemMapper $clotheMapper,
    ) {
    }

    /** @return array<string, mixed> */
    public function map(BestsellerUpdateResult $result): array
    {
        return [
            'success' => $result->success,
            'requiresPruning' => $result->requiresPruning,
            'maxItems' => $result->maxItems,
            'message' => $result->message,
            'items' => array_map(
                fn ($clothe): array => $this->clotheMapper->map($clothe)->toArray(),
                $result->bestsellers,
            ),
            'overflow' => array_map(
                fn ($clothe): array => $this->clotheMapper->map($clothe)->toArray(),
                $result->overflow,
            ),
        ];
    }
}
