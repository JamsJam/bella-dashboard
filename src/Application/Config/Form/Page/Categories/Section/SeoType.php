<?php

namespace App\Application\Config\Form\Page\Categories\Section;

use App\Application\Config\Dto\Page\Categories\Section\SeoDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

final class SeoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Titre SEO'])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 3]])
            ->add('keywords', TextType::class, ['label' => 'Mots-clés'])
            ->add('ogTitle', TextType::class, ['label' => 'Titre Open Graph'])
            ->add('ogDescription', TextareaType::class, ['label' => 'Description Open Graph', 'attr' => ['rows' => 3]])
            ->add('ogUrl', TextType::class, ['label' => 'URL Open Graph'])
            ->add('ogImageFile', FileType::class, [
                'label' => 'Image Open Graph',
                'mapped' => false,
                'required' => false,
                'help' => 'JPEG ou PNG. Laisser vide pour conserver l’image actuelle.',
                'attr' => ['accept' => 'image/jpeg,image/png'],
                'constraints' => [new File(
                    mimeTypes: ['image/jpeg', 'image/png'],
                    mimeTypesMessage: 'Sélectionne une image JPEG ou PNG.',
                )],
            ])
            ->add('jsonLd', TextareaType::class, ['label' => 'JSON-LD', 'attr' => ['rows' => 8]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SeoDto::class]);
    }
}
