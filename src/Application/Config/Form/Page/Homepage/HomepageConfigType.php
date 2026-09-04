<?php

namespace App\Application\Config\Form\Page\Homepage;

use App\Application\Config\Dto\Page\Homepage\HomepageConfigDto;
use App\Application\Config\Form\Page\Homepage\Section\AboutType;
use App\Application\Config\Form\Page\Homepage\Section\LandingType;
use App\Application\Config\Form\Page\Homepage\Section\ManualType;
use App\Application\Config\Form\Page\Homepage\Section\ReturnType;
use App\Application\Config\Form\Page\Homepage\Section\SeoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class HomepageConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('landing', LandingType::class, ['label' => 'Landing'])
            ->add('about', AboutType::class, ['label' => 'À propos'])
            ->add('manual', ManualType::class, ['label' => "Mode d'emploi"])
            ->add('return', ReturnType::class, ['label' => 'Retours et services'])
            ->add('seo', SeoType::class, ['label' => 'SEO']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => HomepageConfigDto::class]);
    }
}
