<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\CarrierDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

final class CarrierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du transporteur',
                'constraints' => [new NotBlank(), new Length(max: 100)],
            ])
            ->add('trackingUrl', UrlType::class, [
                'label' => 'Lien de suivi',
                'help' => 'Le numéro d’expédition sera ajouté à la fin de ce lien.',
                'constraints' => [new NotBlank(), new Length(max: 2048), new Url(protocols: ['https'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CarrierDto::class]);
    }
}
