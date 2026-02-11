<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',

                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        new Assert\NotBlank([
                            'message' => 'Mot de passe obligatoire'
                        ]),
                        new Assert\Length([
                            'min' => 8,
                            'max' => 4096,
                            'minMessage' => 'Minimum {{ limit }} caractères'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/[A-Z]/',
                            'message' => 'Au moins une majuscule'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/[a-z]/',
                            'message' => 'Au moins une minuscule'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/[0-9]/',
                            'message' => 'Au moins un chiffre'
                        ]),
                        new Assert\Regex([
                            'pattern' => '/[\W]/',
                            'message' => 'Au moins un caractère spécial'
                        ]),
                        new Assert\PasswordStrength(),
                        new Assert\NotCompromisedPassword(),
                    ],
                ],

                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}