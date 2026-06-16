<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\GeneralConfigDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class GeneralConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siteTitle', TextType::class, [
                'label' => 'Titre du site',
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo du site',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : PNG, JPEG ou WebP.',
                        'maxSizeMessage' => 'Le logo ne doit pas dépasser 2 Mo.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/png,image/jpeg,image/webp',
                    'class' => 'config-file__input',
                ],
                'help' => 'PNG, JPEG ou WebP. 2 Mo maximum.',
            ])
            ->add('faviconFile', FileType::class, [
                'label' => 'Favicon',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '512k',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : PNG, JPEG ou WebP.',
                        'maxSizeMessage' => 'Le favicon ne doit pas dépasser 512 Ko.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/png,image/jpeg,image/webp',
                    'class' => 'config-file__input',
                ],
                'help' => 'PNG, JPEG ou WebP. 512 Ko maximum.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GeneralConfigDto::class,
        ]);
    }
}
