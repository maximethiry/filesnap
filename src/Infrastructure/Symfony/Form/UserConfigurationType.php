<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Form;

use App\Infrastructure\FormatConverter\CommonFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<FormBuilder>
 */
final class UserConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        dump($options);

        $builder
            ->add('formats', EnumType::class, [
                'class' => CommonFormat::class,
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('snapExpirationDaysInterval', NumberType::class);
    }
}
