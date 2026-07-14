<?php

namespace App\Application\Config\Form\Page\Categories;

use App\Application\Config\Dto\Page\Categories\CategoriesConfigDto;
use App\Application\Config\Form\Page\Categories\Section\BandeauType;
use App\Application\Config\Form\Page\Categories\Section\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CategoriesConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bandeau', BandeauType::class, ['label' => 'Bandeau'])
            ->add('seo', SeoType::class, ['label' => 'SEO']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CategoriesConfigDto::class]);
    }
}
