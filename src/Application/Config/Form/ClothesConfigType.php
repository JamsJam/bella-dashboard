<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\ClothesConfigDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClothesConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bestsellerCount', IntegerType::class, [
                'label' => 'Nombre de vêtements dans les bestsellers',
                'attr' => ['min' => 0],
            ])
            ->add('featuredCount', IntegerType::class, [
                'label' => 'Nombre de vêtements mis en avant',
                'attr' => ['min' => 0],
            ])
            ->add('sizeGuideItems', CollectionType::class, [
                'label' => 'Items disponibles dans le guide des tailles',
                'entry_type' => SizeGuideItemType::class,
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
            'data_class' => ClothesConfigDto::class,
        ]);
    }
}
