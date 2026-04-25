<?php

namespace App\Form\Clothes\Category;

use App\DTO\Clothes\Category\CategoryDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\Clothes\Collections\CollectionsInCategoryForm;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class NewCategoryForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, [
                'label' => 'Nom de la catégorie'
            ])

            ->add('metaDescription',TextareaType::class, [
                'label' => 'Meta description',
                'help' => 'Description de la page. maxe 100 caractères',
                'help_attr' =>[
                    'style' => 'color:white'
                ]
            ])

            ->add('image',FileType::class, [

            ])

            ->add('collections', CollectionType::class,[
                'entry_type' => CollectionsInCategoryForm::class,
                'entry_options' => [
                    "label" => false,
                ],
                'label' => false,
                'allow_add' => true,
                'allow_delete' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => CategoryDTO::class
        ]);
    }
}
