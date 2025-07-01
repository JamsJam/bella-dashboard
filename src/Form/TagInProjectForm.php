<?php

namespace App\Form;


use App\Entity\Tag;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagInProjectForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class,[
                "label" => false,
                'row_attr' =>[
                    "data-controller" => "autocomplete",
                    "data-autocomplete-target" => "container",
                    "data-autocomplete-url-provider-value"=>"/api/tags",
                    "data-autocomplete-property-name-value"=>"name",
                    'class' => 'autocomplete'
                ],
                'attr' =>[
                    "data-autocomplete-target" => "input",
                    'data-action' => "focus->autocomplete#displayOnFocus input->autocomplete#searchOnChange "
                ]

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tag::class,
        ]);
    }
}
