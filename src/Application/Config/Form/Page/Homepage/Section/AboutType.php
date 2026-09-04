<?php

namespace App\Application\Config\Form\Page\Homepage\Section;

use App\Application\Config\Dto\Page\Homepage\Section\AboutDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AboutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre'])
            ->add('text', TextareaType::class, [
                'label' => 'Texte',
                'help' => 'Pour créer un saut de ligne, ajoutez <br>.',
                'attr' => ['rows' => 7],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AboutDto::class]);
    }
}
