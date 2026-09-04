<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\PageSectionDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class PageSectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', TextType::class, [
                'label' => 'Type de section',
                'required' => false,
            ])
            ->add('contentType', ChoiceType::class, [
                'label' => 'Type de contenu',
                'attr' => [
                    'data-config-content-type-select' => 'true',
                ],
                'choices' => [
                    'Texte' => PageSectionDto::CONTENT_TYPE_TEXT,
                    'Liste' => PageSectionDto::CONTENT_TYPE_LIST,
                    'Image' => PageSectionDto::CONTENT_TYPE_IMAGE,
                    'Bestseller' => PageSectionDto::CONTENT_TYPE_BESTSELLER,
                ],
            ])
            ->add('text', TextType::class, [
                'label' => 'Texte',
                'required' => false,
                'row_attr' => ['data-config-content-field' => PageSectionDto::CONTENT_TYPE_TEXT],
            ])
            ->add('image', HiddenType::class, [
                'required' => false,
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : PNG, JPEG ou WebP.',
                        'maxSizeMessage' => 'L’image ne doit pas dépasser 2 Mo.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/png,image/jpeg,image/webp',
                    'class' => 'config-file__input',
                ],
                'help' => 'PNG, JPEG ou WebP. 2 Mo maximum.',
                'row_attr' => ['data-config-content-field' => PageSectionDto::CONTENT_TYPE_IMAGE],
            ])
            ->add('listItems', CollectionType::class, [
                'label' => 'Liste',
                'entry_type' => PageSectionContentItemType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'entry_options' => ['label' => false],
                'row_attr' => ['data-config-content-field' => PageSectionDto::CONTENT_TYPE_LIST],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageSectionDto::class,
        ]);
    }
}
