<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new Assert\NotBlank(['message'=>'Le prénom est obligatoire']),
                    new Assert\Length(['min'=>2,'max'=>50]),
                    new Assert\Regex([
                        'pattern'=>"/^[a-zA-ZÀ-ÿ\s'-]+$/u",
                        'message'=>'Caractères invalides dans le prénom'
                    ])
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new Assert\NotBlank(['message'=>'Le nom est obligatoire']),
                    new Assert\Length(['min'=>2,'max'=>50]),
                    new Assert\Regex([
                        'pattern'=>"/^[a-zA-ZÀ-ÿ\s'-]+$/u",
                        'message'=>'Caractères invalides dans le nom'
                    ])
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints'=>[
                    new Assert\NotBlank(['message'=>'Email obligatoire']),
                    new Assert\Email(['message'=>'Email invalide']),
                    new Assert\Length(['max'=>180])
                ]
            ])
            ->add('phone', TextType::class, [
                'required'=>false,
                'label'=>'Téléphone',
                'constraints'=>[
                    new Assert\Regex([
                        'pattern'=>'/^(\+216)?[2459]\d{7}$/',
                        'message'=>'Numéro tunisien invalide'
                    ])
                ]
            ])
            ->add('address', TextType::class, [
                'required'=>false,
                'label'=>'Adresse',
                'constraints'=>[
                    new Assert\Length(['min'=>5,'max'=>255])
                ]
            ])
            ->add('documentFileFile', FileType::class, [
                'label'=>'Document justificatif',
                'required'=>false,
                'mapped'=>false,
                'constraints'=>[
                    new Assert\File([
                        'maxSize'=>'5M',
                        'mimeTypes'=>['image/jpeg','image/png','application/pdf'],
                        'mimeTypesMessage'=>'PDF, JPEG ou PNG seulement'
                    ])
                ]
            ])
            ->add('imageFile', FileType::class, [
                'label'=>'Photo de profil',
                'required'=>false,
                'mapped'=>false,
                'constraints'=>[
                    new Assert\File([
                        'maxSize'=>'5M',
                        'mimeTypes'=>['image/jpeg','image/png'],
                        'mimeTypesMessage'=>'JPEG ou PNG seulement'
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'=>User::class,
        ]);
    }
}