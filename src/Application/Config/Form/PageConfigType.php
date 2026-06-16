<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\PageConfigDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PageConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('slug', TextType::class, ['label' => 'Slug'])
            ->add('seoTitle', TextType::class, ['label' => 'Titre SEO', 'required' => false])
            ->add('seoMetadescription', TextareaType::class, [
                'label' => 'Metadescription',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('jsonLdYaml', TextareaType::class, [
                'label' => 'JSON-LD',
                'required' => false,
                'attr' => ['rows' => 8],
            ])
            ->add('openGraphYaml', TextareaType::class, [
                'label' => 'OpenGraph',
                'required' => false,
                'attr' => ['rows' => 8],
            ])
            ->add('sections', CollectionType::class, [
                'label' => 'Sections',
                'entry_type' => PageSectionType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'entry_options' => ['label' => false],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PageConfigDto::class,
        ]);
    }
}
