<?php

namespace App\Form\Clothes\Collections;

use Symfony\Component\Form\AbstractType;
use App\DTO\Clothes\Category\CategoryDTO;
use App\DTO\Clothes\Collections\CollectionsDTO;
use App\Entity\Category\Category;
use App\Form\Clothes\Collections\SizeGuideForm;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Form\Clothes\Clothes\ClothesInCollections;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\Form\Clothes\Collections\CollectionsInCategoryForm;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class EditCollectionsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, [
                'label' => 'Nom de la collection',
                'row_attr' =>[
                    "data-controller"=>"word-count",
                    "data-word-count-max-value"=> "200"

                ],
                'attr' =>[
                    'maxlength' => "50",

                    "placeholder" => "Collection été....",
                    'data-word-count-target'=>"input"
                ],
                'help' => '0/50 caracteres max',
                'help_attr' =>[
                    'style' => "color:white",
                    'data-word-count-target'=>"help"
                ]
            ])

            ->add('category',EntityType::class,[
                'class' => Category::class,
                'choice_label' => 'name',
                'multiple' => false,
                'expanded' => false,
                'label' => 'Categorie',
                'placeholder' => 'Choisir une catégorie',
                "row_attr" =>[
                    "class"=>"select__container"
                ],
                "attr" =>[
                    "class"=>"select__input"
                ]
                ])

            ->add('image',FileType::class, [

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => CollectionsDTO::class
        ]);
    }
}
