<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\User;
use App\Entity\Parcelle;
use App\Entity\Culture;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;


class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre',
                'constraints' => [
            new Assert\NotBlank([
            'message' => 'Le titre est obligatoire.',
                ]),
            new Assert\Length([
            'min' => 3,
            'max' => 255,
            'minMessage' => 'Le titre doit contenir au moins {{ limit }} caractères.',
            'maxMessage' => 'Le titre ne peut pas dépasser {{ limit }} caractères.',
                            ]),
                                ],
            ])

            ->add('description', TextareaType::class, [
    'label' => 'Description',
    'required' => false,
    'constraints' => [
        new Assert\Length([
            'max' => 255,
            'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
        ]),
    ],
])

            ->add('dateDebut', DateTimeType::class, [
    'label' => 'Date de début',
    'widget' => 'single_text',
    'constraints' => [
        new Assert\NotNull([
            'message' => 'La date de début est obligatoire.',
        ]),
        new Assert\GreaterThanOrEqual([
            'value' => 'today',
            'message' => 'La date de début ne peut pas être dans le passé.',
        ]),
    ],
])

            ->add('dateFin', DateTimeType::class, [
    'label' => 'Date de fin',
    'widget' => 'single_text',
    'required' => false,
    'constraints' => [
        new Assert\GreaterThanOrEqual([
            'propertyPath' => 'parent.all[dateDebut].data',
            'message' => 'La date de fin doit être postérieure ou égale à la date de début.',
        ]),
    ],
])
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => 'low',
                    'Moyenne' => 'medium',
                    'Haute' => 'high',
                ],
            ])
            ->add('statut', ChoiceType::class, [
    'label' => 'Statut',
    'choices' => [
        'À faire' => 'todo',
        'En cours' => 'en_cours',
        'Terminé' => 'termine',
    ],
    'constraints' => [
        new Assert\Choice(['À faire', 'en_cours', 'Terminé']),
    ],
])

            ->add('type', ChoiceType::class, [
    'label' => 'Type de tâche',
    'choices' => [
        'Arrosage' => 'arrosage',
        'Récolte' => 'recolte',
        'Fertilisation' => 'fertilisation',
        'Traitement' => 'traitement',
        'Autre' => 'autre',
    ],
    'constraints' => [
        new Assert\Choice(['arrosage', 'recolte', 'fertilisation', 'traitement', 'autre']),
    ],
])

            ->add('localisation', TextType::class, [
    'label' => 'Localisation / Parcelle',
    'required' => false,
    'constraints' => [
        new Assert\Length([
            'max' => 255,
            'maxMessage' => 'La localisation ne peut pas dépasser {{ limit }} caractères.',
        ]),
    ],
])

            ->add('parcelleId', EntityType::class, [
    'class' => Parcelle::class,
    'choice_label' => function (Parcelle $parcelle): string {
        return sprintf(
            'ID: %d - %s',
            $parcelle->getId(),
            $parcelle->getNom()
        );
    },
    'label' => 'Parcelle',
    'placeholder' => 'Sélectionner une parcelle',
    'required' => false,
    'mapped' => false, 
])

            ->add('culture', EntityType::class, [
    'class' => Culture::class,
    'choice_label' => function (Culture $culture): string {
        return sprintf(
            'ID: %d - %s',
            $culture->getId(),
            $culture->getTypeCulture()
        );
    },
    'label' => 'Culture',
    'placeholder' => 'Sélectionner une culture',
    'required' => false,
])
            // Champ pour choisir un utilisateur existant, sans modifier l'entité Task (on stocke toujours l'id)
            ->add('createdByUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user): string {
                    return sprintf('%s %s (%s)',$user->getId(), $user->getFirstName(), $user->getLastName());
                },
                'label' => 'Créée par',
                'required' => false,
                'placeholder' => 'Sélectionner un utilisateur',
                'mapped' => false,
            ])
        ;
                 }           

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }
}

