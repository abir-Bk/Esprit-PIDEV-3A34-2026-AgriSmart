<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Votre prénom'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['placeholder' => 'Votre nom'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['placeholder' => 'exemple@domaine.com'],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Type de compte',
                'choices' => [
                    'Agriculteur'    => 'agriculteur',
                    'Fournisseur'    => 'fournisseur',
                    'Employé'        => 'employee',
                    'Administrateur' => 'admin',
                ],
                'placeholder' => 'Choisissez votre rôle',
                'required'    => true,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr'  => ['placeholder' => '••••••••'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => ['placeholder' => '••••••••'],
                ],
                'mapped'       => false,
                'constraints'  => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins 6 caractères'),
                ],
            ])
            ->add('phone', TextType::class, [
                'label'    => 'Téléphone',
                'required' => false,
                'attr'     => ['placeholder' => '+216 12 345 678'],
            ])
            ->add('address', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
                'attr'     => ['placeholder' => 'Votre adresse complète'],
            ])

            ->add('documentFileFile', FileType::class, [
                'label'    => 'Document justificatif',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png', 'application/pdf'],
                        'mimeTypesMessage' => 'Veuillez uploader un fichier PDF, JPEG ou PNG valide',
                    ])
                ],
            ])

            ->add('imageFile', FileType::class, [
                'label'    => 'Photo de profil',
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize'   => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez uploader une image JPEG ou PNG',
                    ])
                ],
            ])
                    ;
                }
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}