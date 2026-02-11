<?php
// src/Form/DemandeType.php
namespace App\Form;

use App\Entity\Demande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DemandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $demande = $options['data'] ?? null;
    $isEdit = $demande && $demande->getId() !== null;
            
    $builder
        ->add('nom')
        ->add('prenom')
        ->add('phoneNumber')
        ->add('cv', FileType::class, [
            'label' => 'Curriculum Vitae (PDF)',
            'mapped' => false,
            'required' => !$isEdit,
            'constraints' => array_merge(
                // 1. Always check file type/size if a file is provided
                [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez télécharger un PDF valide',
                    ])
                ],
                // 2. Only add NotBlank if we are NOT in edit mode
                !$isEdit ? [new Assert\NotBlank(['message' => 'Le CV est obligatoire'])] : []
            ),
        ])
        ->add('lettreMotivation', FileType::class, [
            'label' => 'Lettre de Motivation (PDF)',
            'mapped' => false,
            'required' => !$isEdit,
            'constraints' => array_merge(
                [
                    new Assert\File([
                        'maxSize' => '2M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Format PDF uniquement',
                    ])
                ],
                !$isEdit ? [new Assert\NotBlank(['message' => 'La lettre de motivation est obligatoire'])] : []
            ),
        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Demande::class,
        ]);
    }
}