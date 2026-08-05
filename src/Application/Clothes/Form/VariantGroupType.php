<?php

namespace App\Application\Clothes\Form;

use App\Application\Clothes\DTO\VariantGroupInput;
use App\Entity\Clothes\Clothescolor;
use App\Entity\Clothes\Clothessize;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VariantGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('color', EntityType::class, ['class' => Clothescolor::class, 'choice_label' => 'name', 'placeholder' => 'Créer une nouvelle couleur', 'required' => false])
            ->add('newColorName', TextType::class, ['required' => false, 'label' => 'Nom de la nouvelle couleur'])
            ->add('newColorHex', ColorType::class, ['required' => false, 'label' => 'Couleur'])
            ->add('sizes', EntityType::class, ['class' => Clothessize::class, 'choice_label' => 'name', 'multiple' => true, 'expanded' => true, 'label' => 'Tailles disponibles'])
            ->add('description', TextareaType::class, ['required' => false])
            ->add('metaDescription', TextareaType::class, ['required' => false, 'label' => 'Méta-description'])
            ->add('images', FileType::class, ['multiple' => true, 'mapped' => true, 'label' => 'Images', 'attr' => ['accept' => 'image/png,image/jpeg', 'data-variant-images' => '']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => VariantGroupInput::class]);
    }
}
