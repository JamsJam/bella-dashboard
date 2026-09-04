<?php

namespace App\Application\Clothes\Form;

use App\Application\Clothes\DTO\ClotheFormInput;
use App\Entity\Collections\Collections;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ClotheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('price', IntegerType::class, ['label' => 'Prix TTC en centimes'])
            ->add('collection', EntityType::class, ['class' => Collections::class, 'choice_label' => 'name', 'placeholder' => 'Sélectionner une collection'])
            ->add('variants', CollectionType::class, ['entry_type' => VariantGroupType::class, 'allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ClotheFormInput::class]);
    }
}
