<?php

namespace App\Tests\Clothes\Integration;

use App\Application\Config\Dto\ClothesConfigDto;
use App\Application\Config\Dto\SizeGuideItemDto;
use App\Application\Config\Form\ClothesConfigType;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

/** Vérifie que le formulaire conserve l’UUID technique sans jamais l’exposer à l’utilisateur. */
#[Group('clothes')]
#[Group('integration')]
final class MeasurementTypeConfigFormTest extends KernelTestCase
{
    public function testUuidIsNotAFieldAndSurvivesItemEdition(): void
    {
        self::bootKernel();
        $uuid = '018f3f3e-7b1a-7c00-8000-000000000001';
        $config = new ClothesConfigDto(sizeGuideItems: [
            new SizeGuideItemDto(uuid: $uuid, label: 'Poitrine'),
        ]);
        $formFactory = self::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->create(ClothesConfigType::class, $config, ['csrf_protection' => false]);

        self::assertFalse(
            $form->get('sizeGuideItems')->get(0)->has('uuid'),
            'Blocage : l’UUID technique est visible dans le formulaire.',
        );

        $form->submit([
            'bestsellerCount' => 4,
            'featuredCount' => 4,
            'sizeGuideItems' => [[
                'label' => 'Tour de poitrine',
                'description' => 'Mesure à plat.',
            ]],
        ]);

        self::assertTrue($form->isValid(), 'Blocage : la modification du libellé est refusée.');
        self::assertSame(
            $uuid,
            $config->sizeGuideItems[0]->uuid,
            'Blocage : modifier un item remplace son UUID technique.',
        );
    }
}
