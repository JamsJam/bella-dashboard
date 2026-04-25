<?php

namespace App\Form\Avatar\Color;

use App\DTO\Avatar\Color\ColorDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditColorForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hexa', TextType::class, [
                'label' => 'Code couleur',
                'attr' => [
                    'data-color-preview-target' => 'text_input',
                    'data-action' => 'input->color-preview#colorChangeByInputColor',
                    'maxLength' => 7,
                    'pattern' => '#[0-9A-Fa-f]{6}',
                    'placeholder' => '#123456',
                    'title' => 'Entrer un code couleur hexadécimal',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ColorDTO::class,
        ]);
    }
}
