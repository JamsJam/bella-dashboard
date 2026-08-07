<?php

namespace App\Tests\Application\Config\Dto;

use App\Application\Config\Dto\ClothesConfigDto;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/** Vérifie la configuration sérialisée des items disponibles dans les guides des tailles. */
#[Group('unit')]
final class ClothesConfigDtoTest extends TestCase
{
    public function testYamlConfigurationDoesNotStoreSizeGuideItems(): void
    {
        $config = ClothesConfigDto::fromArray([]);

        self::assertSame([], $config->sizeGuideItems, 'Blocage : le YAML duplique les types de mesure de Doctrine.');
        self::assertArrayNotHasKey('size_guide_items', $config->toArray());
    }

    public function testLegacyYamlItemsAreIgnored(): void
    {
        $config = ClothesConfigDto::fromArray([
            'size_guide_items' => [[
                'code' => 'neck_width',
                'label' => 'Largeur du col',
                'description' => 'Mesure à plat.',
            ]],
        ]);

        self::assertSame([], $config->sizeGuideItems, 'Blocage : une ancienne collection YAML reste utilisée.');
    }
}
