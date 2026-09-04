<?php

namespace App\Application\Clothes\Form;

use App\Application\Clothes\DTO\VariantFormInput;
use App\Entity\Clothes\Clothes;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class VariantType extends VariantGroupType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('clothe', EntityType::class, [
            'class' => Clothes::class,
            'choice_label' => 'name',
            'placeholder' => 'Sélectionner un vêtement',
            'label' => 'Vêtement',
        ]);
        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => VariantFormInput::class]);
    }
}
