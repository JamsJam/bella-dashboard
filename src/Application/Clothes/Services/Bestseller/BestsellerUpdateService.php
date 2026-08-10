<?php

namespace App\Application\Clothes\Services\Bestseller;

use App\Application\Clothes\DTO\BestsellerUpdateInput;
use App\Application\Clothes\Model\BestsellerUpdateResult;

final readonly class BestsellerUpdateService
{
    public function __construct(
        private ClotheBestsellerService $bestsellerService,
    ) {
    }

    public function update(BestsellerUpdateInput $input): BestsellerUpdateResult
    {
        return match ($input->mode) {
            'replace' => $this->bestsellerService->replaceByIds($input->ids, $input->pruneOverflow),
            'remove' => $this->bestsellerService->removeBySlugs($input->slugs),
            default => $this->bestsellerService->addByIds($input->ids, $input->pruneOverflow),
        };
    }
}
