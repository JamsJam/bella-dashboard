<?php

namespace App\Application\Config\Form\Page\Homepage\Section;

use App\Application\Config\Dto\Page\Homepage\Section\ManualDto;
use App\Application\Config\Form\Page\Homepage\Item\ManualItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ManualType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre principal',
                'required' => false,
                'empty_data' => null,
            ])
            ->add('list', CollectionType::class, ['label' => 'Étapes', 'entry_type' => ManualItemType::class, 'by_reference' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ManualDto::class]);
    }
}
