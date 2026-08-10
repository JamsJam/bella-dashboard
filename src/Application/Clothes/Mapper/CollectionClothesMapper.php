<?php

namespace App\Application\Clothes\Mapper;

use App\Application\Clothes\Guard\ClotheOnlineGuard;
use App\Application\Clothes\Services\Clothe\ClotheService;
use App\Entity\Clothes\Clothes;
use App\Entity\Collections\Collections;
use App\Enum\ClotheStatus;

final readonly class CollectionClothesMapper
{
    public function __construct(
        private ClotheOnlineGuard $onlineGuard,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function map(Collections $collection): array
    {
        $items = [];

        foreach ($collection->getClothes() as $clothe) {
            if (!$clothe instanceof Clothes) {
                continue;
            }

            $slug = (string) $clothe->getSlug() ?: 'clothe-' . $clothe->getId();
            if (!isset($items[$slug])) {
                $images = $clothe->getImages() ?? [];
                $items[$slug] = [
                    'name' => (string) $clothe->getName(),
                    'slug' => $slug,
                    'image' => $images[0] ?? $collection->getImage(),
                    'isOnline' => $this->onlineGuard->areVariantsOnline($clothe->getVariants()->toArray()),
                    'canPublish' => $this->onlineGuard->canPublish($clothe)->canPublish(),
                    'sizes' => [],
                ];
            }

            foreach ($clothe->getVariants() as $variant) {
                $size = $variant->getSize()?->getName();
                if (null !== $size && '' !== $size) {
                    $items[$slug]['sizes'][$size] = [
                        'name' => $size,
                        'isOnline' => ClotheStatus::Online === $variant->getPublicationStatus(),
                        'stock' => $variant->getStock(),
                    ];
                }
            }
        }

        foreach ($items as &$item) {
            $item['sizes'] = array_values($item['sizes']);
            usort($item['sizes'], static function (array $a, array $b): int {
                $left = array_search($a['name'], ClotheService::AVAILABLE_SIZES, true);
                $right = array_search($b['name'], ClotheService::AVAILABLE_SIZES, true);

                return (false === $left ? 999 : $left) <=> (false === $right ? 999 : $right);
            });
        }
        unset($item);

        return array_values($items);
    }
}
