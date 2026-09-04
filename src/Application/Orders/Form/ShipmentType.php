<?php

namespace App\Application\Orders\Form;

use App\Application\Config\Dto\CarrierDto;
use App\Application\Orders\Dto\ShipmentDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach ($options['carriers'] as $index => $carrier) {
            $choices[$carrier->name] = (string) $index;
        }

        $builder
            ->add('trackingNumber', TextType::class, [
                'label' => 'Numéro d’expédition',
                'attr' => ['maxlength' => 255, 'autocomplete' => 'off', 'autofocus' => true],
                'help' => 'Ce numéro sera communiqué au client par e-mail.',
            ])
            ->add('carrier', ChoiceType::class, [
                'label' => 'Transporteur',
                'placeholder' => 'Choisir un transporteur',
                'choices' => $choices,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ShipmentDto::class]);
        $resolver->setRequired('carriers');
        $resolver->setAllowedTypes('carriers', 'array');
        $resolver->setAllowedValues('carriers', static function (array $carriers): bool {
            foreach ($carriers as $carrier) {
                if (!$carrier instanceof CarrierDto) {
                    return false;
                }
            }

            return true;
        });
    }
}
