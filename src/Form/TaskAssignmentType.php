<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\TaskAssignment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('task', EntityType::class, [
                'class' => Task::class,
                'choice_label' => 'titre',
                'label' => 'Tâche',
            ])
            ->add('workerId', IntegerType::class, [
                'label' => 'Ouvrier (ID utilisateur)',
            ])
            ->add('dateAssignment', DateTimeType::class, [
                'label' => 'Date d\'affectation',
                'widget' => 'single_text',
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Assignée' => 'assignee',
                    'Acceptée' => 'acceptee',
                    'Réalisée' => 'realisee',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskAssignment::class,
        ]);
    }
}

