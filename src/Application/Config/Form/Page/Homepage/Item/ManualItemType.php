<?php

namespace App\Application\Config\Form\Page\Homepage\Item;

use App\Application\Config\Dto\Page\Homepage\Item\ManualItemDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class ManualItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('text', TextType::class, ['label' => 'Texte'])
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
        $resolver->setDefaults(['data_class' => ManualItemDto::class]);
    }
}
