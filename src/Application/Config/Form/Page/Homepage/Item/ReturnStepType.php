<?php

namespace App\Application\Config\Form\Page\Homepage\Item;

use App\Application\Config\Dto\Page\Homepage\Item\ReturnStepDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class ReturnStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('iconFile', FileType::class, [
                'label' => 'Icône',
                'mapped' => false,
                'required' => false,
                'help' => 'JPEG, PNG ou SVG. Laisser vide pour conserver l’icône actuelle.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/svg+xml'],
                'constraints' => [
                    new File(
                        mimeTypes: ['image/jpeg', 'image/png', 'image/svg+xml'],
                        mimeTypesMessage: 'Sélectionne une image JPEG, PNG ou SVG.',
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ReturnStepDto::class]);
    }
}
