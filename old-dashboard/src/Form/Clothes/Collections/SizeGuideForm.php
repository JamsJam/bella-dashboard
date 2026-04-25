<?php

namespace App\Form\Clothes\Collections;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use App\DTO\Clothes\Collections\SizeGuideItemDTO;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SizeGuideForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clotheSize',TextType::class,[])
            ->add('title',TextType::class,[])
            ->add('size',TextType::class,[])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => SizeGuideItemDTO::class
        ]);
    }
}
