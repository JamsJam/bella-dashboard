<?php

namespace App\Application\Config\Form;

use App\Application\Config\Dto\GeneralConfigDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Timezone;

final class GeneralConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('siteTitle', TextType::class, [
                'label' => 'Titre du site',
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'Fuseau horaire',
                'help' => 'Utilisé pour saisir et afficher les dates. Les données restent enregistrées en UTC.',
                'choice_loader' => null,
                'choices' => self::sortedTimezoneChoices(),
                'choice_label' => static function (string $timezone): string {
                    $offset = self::offset($timezone);
                    $sign = $offset >= 0 ? '+' : '-';
                    $hours = intdiv(abs($offset), 3600);
                    $minutes = intdiv(abs($offset) % 3600, 60);

                    return sprintf('(UTC%s%02d:%02d) %s', $sign, $hours, $minutes, $timezone);
                },
                'constraints' => [
                    new NotBlank(message: 'Choisissez un fuseau horaire.'),
                    new Timezone(message: 'Choisissez un fuseau horaire valide.'),
                ],
            ])
            ->add('logoFile', FileType::class, [
                'label' => 'Logo du site',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : PNG, JPEG ou WebP.',
                        'maxSizeMessage' => 'Le logo ne doit pas dépasser 2 Mo.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/png,image/jpeg,image/webp',
                    'class' => 'config-file__input',
                ],
                'help' => 'PNG, JPEG ou WebP. 2 Mo maximum.',
            ])
            ->add('faviconFile', FileType::class, [
                'label' => 'Favicon',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '512k',
                        'mimeTypes' => ['image/png', 'image/jpeg', 'image/webp'],
                        'mimeTypesMessage' => 'Formats acceptés : PNG, JPEG ou WebP.',
                        'maxSizeMessage' => 'Le favicon ne doit pas dépasser 512 Ko.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/png,image/jpeg,image/webp',
                    'class' => 'config-file__input',
                ],
                'help' => 'PNG, JPEG ou WebP. 512 Ko maximum.',
            ]);
    }

    /** @return array<string, string> */
    private static function sortedTimezoneChoices(): array
    {
        $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::ALL);
        usort($timezones, static function (string $left, string $right): int {
            $offsetComparison = self::offset($left) <=> self::offset($right);

            return 0 !== $offsetComparison ? $offsetComparison : $left <=> $right;
        });

        $choices = [];
        foreach ($timezones as $timezone) {
            $choices[$timezone] = $timezone;
        }

        return $choices;
    }

    private static function offset(string $timezone): int
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone($timezone)))->getOffset();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GeneralConfigDto::class,
        ]);
    }
}
