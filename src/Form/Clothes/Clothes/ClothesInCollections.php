<?php
namespace App\Form\Clothes\Clothes;

use App\DTO\Clothes\Clothes\ClothesDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ClothesInCollections extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, [
                'label' => 'Nom du vetement',
                'row_attr' =>[
                    "data-controller"=>"word-count",
                    "data-word-count-max-value"=> "200"
                ],
                'attr' =>[
                    'maxlength' => "50",
                    "placeholder" => "Nom du produit",
                    'data-word-count-target'=>"input"
                ],
                'help' => '0/50 caracteres max',
                'help_attr' =>[
                    'style' => "color:white",
                    'data-word-count-target'=>"help"
                ]
            ])
            ->add('color',TextType::class, [
                'label' => 'Couleur du vetement',
                'row_attr' => [
                    'data-controller' => 'autocomplete',
                    'data-autocomplete-target' => 'container',
                    'data-autocomplete-url-provider-value' => '/api/clothescolor',
                    'data-autocomplete-property-name-value' => 'name',
                    'class' => 'autocomplete',
                ],
                'attr' => [
                    'data-autocomplete-target' => 'input',
                    'data-action' => 'focus->autocomplete#displayOnFocus input->autocomplete#searchOnChange',
                ],
            ])
            ->add('size',TextType::class, [
                'label' => false,
                'required' => true,
                'row_attr' => [
                    'data-controller' => 'tag-collection',
                    'data-tag-collection-target' => 'container',
                    'data-tag-collection-false-input-label-value' => 'Tailles des vetements',
                    'data-tag-collection-with-autocomplete-value' => true,
                    'data-tag-collection-autocomplete-link-value' => '/api/clothessize',

                ],
                // 'attr' => [
                //     'data-tag-collection-target' => 'trueInput',
                // ],
            ])

            ->add('description',TextareaType::class, [
                'label' => 'Description du vetement',
                'row_attr' =>[
                    "data-controller"=>"word-count",
                    "data-word-count-max-value"=> "200"
                ],
                'attr' =>[
                    'maxlength' => "200",
                    "spellcheck" => "true",
                    "placeholder" => "Description du produit",
                    'data-word-count-target'=>"input"
                ],
                'help' => '0/200 caracteres max',
                'help_attr' =>[
                    'style' => "color:white",
                    'data-word-count-target'=>"help"
                ]
            ])
            ->add('metadescription',TextareaType::class, [
                'label' => 'Meta-description du vetement',
                'row_attr' =>[
                    "data-controller"=>"word-count",
                    "data-word-count-max-value"=> "200"

                ],
                'attr' =>[
                    'maxlength' => "200",
                    "spellcheck" => "true",
                    "placeholder" => "Description pour le referencement",
                    'data-word-count-target'=>"input"
                ],
                'help' => '0/200 caracteres max',
                'help_attr' =>[
                    'style' => "color:white",
                    'data-word-count-target'=>"help"
                ]
            ])

            ->add('price',MoneyType::class, [
                'label' => 'Prix TTC du vetement',
                'divisor' => 100,
                'currency' => 'eur',
                'grouping' => true,
                'rounding_mode' => \NumberFormatter::ROUND_FLOOR,
                'scale' => 2,
                'input' => "string",
                'attr' =>[
                    // 'pattern' => '^\d{1,3}(?:,\d{3})*(?:\.\d{1,2})?$/g',
                    "placeholder" => "Prix du produit en €"
                ]

            ])
            ->add('stock',NumberType::class, [
                'label' => 'Stock du vetement',
                'scale' => 0,
                'input' => 'string',
                'rounding_mode' => \NumberFormatter::ROUND_DOWN,
                'attr' =>[
                    // 'pattern' => '^\d+$/g',
                    "placeholder" => "Stock du produit"
                ]
            ])
            ->add('images',FileType::class, [
                'label' => 'Images du vetement',
                "multiple" => true,
                'attr' => [
                    'data-dropzone-preview-target'=>"fileInput"
                ]
                // multiple, 5 max, choix de l'image central 

            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
            'data_class' => ClothesDTO::class,
            'allow_extra' => true
        ]);
    }
}
