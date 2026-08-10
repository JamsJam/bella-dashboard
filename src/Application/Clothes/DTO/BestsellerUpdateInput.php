<?php

namespace App\Application\Clothes\DTO;

final readonly class BestsellerUpdateInput
{
    /**
     * @param list<int>    $ids
     * @param list<string> $slugs
     */
    public function __construct(
        public array $ids,
        public array $slugs,
        public string $mode,
        public bool $pruneOverflow,
        public string $csrfToken,
        public bool $wantsTurboStream,
        public bool $wantsJson,
        public bool $isXmlHttpRequest,
    ) {
    }
}
