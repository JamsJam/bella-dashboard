<?php

namespace App\Form;

use App\Entity\Project;
use App\Entity\Technology;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TechnologiesInProjectForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => false,
                'row_attr' => [
                    'data-controller' => 'autocomplete',
                    'data-autocomplete-target' => 'container',
                    'data-autocomplete-url-provider-value' => '/api/technologies',
                    'data-autocomplete-property-name-value' => 'name',
                    'class' => 'autocomplete',
                ],
                'attr' => [
                    'data-autocomplete-target' => 'input',
                    'data-action' => 'focus->autocomplete#displayOnFocus input->autocomplete#searchOnChange ',
                ],
            ])
            // ->add('projects', EntityType::class, [
            //     'class' => Project::class,
            //     'choice_label' => 'id',
            //     'multiple' => true,
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Technology::class,
        ]);
    }
}
