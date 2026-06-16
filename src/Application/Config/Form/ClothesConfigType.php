<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\ClothesConfigDto;
use Symfony\Component\Form\AbstractType;
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ClothesConfigDto::class,
        ]);
    }
}
