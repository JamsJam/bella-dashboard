<?php

namespace App\Application\Config\Form\Page\Homepage\Section;

use App\Application\Config\Dto\Page\Homepage\Section\ReturnDto;
use App\Application\Config\Form\Page\Homepage\Item\ReturnStepType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ReturnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('steps', CollectionType::class, ['label' => 'Éléments', 'entry_type' => ReturnStepType::class, 'by_reference' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ReturnDto::class]);
    }
}
