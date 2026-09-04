<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\ContactConfigDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ContactConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('ownerEmail', EmailType::class, ['label' => 'Email du propriétaire', 'required' => false])
            ->add('ownerName', TextType::class, ['label' => 'Nom du propriétaire', 'required' => false])
            ->add('ownerSocialNetworks', CollectionType::class, [
                'label' => 'Réseaux sociaux',
                'entry_type' => SocialNetworkType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype' => true,
                'entry_options' => ['label' => false],
            ])
            ->add('developerContact', DeveloperContactType::class, [
                'label' => 'Contact du développeur',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactConfigDto::class,
        ]);
    }
}
