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

            // =========================
            // TITRE
            // =========================
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
                    new Assert\Regex([
                        'pattern' => "/^[a-zA-ZÀ-ÿ0-9\s\-',.()]+$/u",
                        'message' => 'Caractères invalides dans le titre.',
                    ]),
                ],
            ])

            // =========================
            // DESCRIPTION
            // =========================
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La description est obligatoire.',
                    ]),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 2000,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])

            // =========================
            // RESUME (AI generated)
            // =========================
            ->add('resume', TextareaType::class, [
                'label' => 'Résumé (automatique)',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Le résumé sera généré automatiquement ou vous pouvez le saisir manuellement...',
                    'rows' => 3
                ],
            ])

            // =========================
            // DATE DEBUT
            // =========================
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'required' => true, // obligatoire
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


            // =========================
            // DATE FIN
            // =========================
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

            // =========================
            // PRIORITE
            // =========================
            ->add('priorite', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => 'low',
                    'Moyenne' => 'medium',
                    'Haute' => 'high',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'La priorité est obligatoire.',
                    ]),
                    new Assert\Choice([
                        'choices' => ['low', 'medium', 'high'],
                        'message' => 'Priorité invalide.',
                    ]),
                ],
            ])

            // =========================
            // STATUT
            // =========================
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'À faire' => 'todo',
                    'En cours' => 'en_cours',
                    'Terminé' => 'termine',
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Le statut est obligatoire.',
                    ]),
                    new Assert\Choice([
                        'choices' => ['todo', 'en_cours', 'termine'],
                        'message' => 'Statut invalide.',
                    ]),
                ],
            ])

            // =========================
            // TYPE
            // =========================
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
                    new Assert\NotBlank([
                        'message' => 'Le type est obligatoire.',
                    ]),
                    new Assert\Choice([
                        'choices' => ['arrosage', 'recolte', 'fertilisation', 'traitement', 'autre'],
                        'message' => 'Type invalide.',
                    ]),
                ],
            ])

            // =========================
            // LOCALISATION
            // =========================
            ->add('localisation', TextType::class, [
                'label' => 'Localisation / Parcelle',
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La localisation ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^[a-zA-ZÀ-ÿ0-9\s\-',.()]*$/u",
                        'message' => 'Caractères invalides.',
                    ]),
                ],
            ])

            // =========================
            // PARCELLE
            // =========================
            ->add('parcelleId', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => fn(Parcelle $parcelle) =>
                $parcelle->getNom() . ' (' . $parcelle->getSurface() . ' ha)',
                'label' => 'Parcelle',
                'placeholder' => 'Sélectionner une parcelle',
                'required' => false,
                'mapped' => false,
                'query_builder' => isset($options['user']) ? function ($er) use ($options) {
                    return $er->createQueryBuilder('p')
                        ->where('p.user = :user')
                        ->setParameter('user', $options['user'])
                        ->orderBy('p.nom', 'ASC');
                } : null,
            ])

            // =========================
            // CULTURE
            // =========================
            ->add('culture', EntityType::class, [
                'class' => Culture::class,
                'choice_label' => fn(Culture $culture) =>
                $culture->getTypeCulture() . ' – ' . $culture->getVariete(),
                'label' => 'Culture',
                'placeholder' => 'Sélectionner une culture',
                'required' => false,
            ])

            // =========================
            // CREATED BY USER
            // =========================
            ->add('createdByUser', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn(User $user) =>
                sprintf(
                    '%d - %s %s',
                    $user->getId(),
                    $user->getFirstName(),
                    $user->getLastName()
                ),
                'label' => 'Créée par',
                'required' => false,
                'placeholder' => 'Sélectionner un utilisateur',
                'mapped' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', ['null', \App\Entity\User::class]);
    }
}
