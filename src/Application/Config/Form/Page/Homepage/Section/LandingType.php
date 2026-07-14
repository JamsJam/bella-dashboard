<?php

namespace App\Application\Config\Form\Page\Homepage\Section;

use App\Application\Config\Dto\Page\Homepage\Section\LandingDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class LandingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('isFullscreen', CheckboxType::class, ['label' => 'Plein écran', 'required' => false])
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('subtitle', TextType::class, ['label' => 'Sous-titre'])
            ->add('text', TextareaType::class, [
                'label' => 'Texte',
                'help' => 'Pour créer un saut de ligne, ajoutez <br>.',
                'attr' => ['rows' => 5],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => false,
                'help' => 'JPEG ou PNG. Laisser vide pour conserver l’image actuelle.',
                'attr' => ['accept' => 'image/jpeg,image/png'],
                'constraints' => [
                    new File(
                        mimeTypes: ['image/jpeg', 'image/png'],
                        mimeTypesMessage: 'Sélectionne une image JPEG ou PNG.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => LandingDto::class]);
    }
}
