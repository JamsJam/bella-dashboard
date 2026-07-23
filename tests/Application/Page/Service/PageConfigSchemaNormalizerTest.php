<?php

namespace App\Tests\Application\Page\Service;

use App\Application\Page\Service\PageConfigSchemaNormalizer;
use PHPUnit\Framework\TestCase;

final class PageConfigSchemaNormalizerTest extends TestCase
{
    public function testItKeepsDefaultShapeAndAppliesSavedValues(): void
    {
        $defaults = [
            'section' => [
                'title' => 'Titre par défaut',
                'enabled' => true,
                'items' => [
                    ['title' => 'Premier', 'text' => 'Texte 1'],
                    ['title' => 'Deuxième', 'text' => 'Texte 2'],
                ],
            ],
        ];
        $saved = [
            'section' => [
                'title' => 'Titre enregistré',
                'enabled' => false,
                'unknown' => 'Champ obsolète',
                'items' => [
                    ['title' => 'Titre personnalisé', 'text' => ''],
                    ['title' => 42, 'text' => 'Texte personnalisé'],
                    ['title' => 'Élément supplémentaire', 'text' => 'Ignoré'],
                ],
            ],
            'extra' => 'Ignoré',
        ];

        self::assertSame([
            'section' => [
                'title' => 'Titre enregistré',
                'enabled' => false,
                'items' => [
                    ['title' => 'Titre personnalisé', 'text' => 'Texte 1'],
                    ['title' => 'Deuxième', 'text' => 'Texte personnalisé'],
                ],
            ],
        ], (new PageConfigSchemaNormalizer())->normalize($defaults, $saved));
    }

    public function testItUsesDefaultsForMissingEmptyOrInvalidValues(): void
    {
        $defaults = [
            'title' => 'Placeholder',
            'description' => 'Description par défaut',
            'options' => ['color' => 'black'],
        ];

        self::assertSame($defaults, (new PageConfigSchemaNormalizer())->normalize($defaults, [
            'title' => '   ',
            'description' => null,
            'options' => 'type invalide',
        ]));
    }

    public function testNullableDefaultAcceptsANonEmptySavedValue(): void
    {
        $normalizer = new PageConfigSchemaNormalizer();

        self::assertSame(
            ['title' => 'Titre enregistré'],
            $normalizer->normalize(['title' => null], ['title' => 'Titre enregistré']),
        );
        self::assertSame(
            ['title' => null],
            $normalizer->normalize(['title' => null], ['title' => '']),
        );
    }
}
