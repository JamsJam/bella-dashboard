<?php

namespace App\Application\Config\Form\Page\Categories\Section;

use App\Application\Config\Dto\Page\Categories\Section\BandeauDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class BandeauType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('cta', TextType::class, ['label' => 'Appel à l’action'])
            ->add('backgroundFile', FileType::class, [
                'label' => 'Image de fond',
                'mapped' => false,
                'required' => false,
                'help' => 'JPEG ou PNG. Laisser vide pour conserver l’image actuelle.',
                'attr' => ['accept' => 'image/jpeg,image/png'],
                'constraints' => [new File(
                    mimeTypes: ['image/jpeg', 'image/png'],
                    mimeTypesMessage: 'Sélectionne une image JPEG ou PNG.',
                )],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => BandeauDto::class]);
    }
}
