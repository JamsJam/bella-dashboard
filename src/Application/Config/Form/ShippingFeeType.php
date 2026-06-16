<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\ShippingFeeDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class ShippingFeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('destination', TextType::class, ['label' => 'Destination'])
            ->add('flag', HiddenType::class, ['required' => false])
            ->add('flagFile', FileType::class, [
                'label' => 'Drapeau',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '512k',
                        'mimeTypes' => ['image/png'],
                        'mimeTypesMessage' => 'Le drapeau doit être une image PNG.',
                        'maxSizeMessage' => 'Le drapeau ne doit pas dépasser 512 Ko.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/png',
                    'class' => 'config-file__input',
                ],
                'help' => 'PNG uniquement. 512 Ko maximum.',
            ])
            ->add('priceCents', MoneyType::class, [
                'label' => 'Prix',
                'currency' => 'EUR',
                'divisor' => 100,
                'scale' => 2,
                'html5' => true,
                'attr' => ['min' => 0, 'step' => '0.01'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ShippingFeeDto::class,
        ]);
    }
}
