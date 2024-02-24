<?php
declare(strict_types=1);

namespace App\Infrastructure\Symfony\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\PasswordStrength;

final class SetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('adminEmail', EmailType::class, [
                'required' => true
            ])
            ->add('adminPlainPassword', PasswordType::class, [
                'required' => true,
                'constraints' => [
                    new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_STRONG,
                        'message' => 'Your password is too weak, please type a stronger password.'
                    ])
                ]
            ]);
    }
}