<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\TaskAssignment;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
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
            ->add('worker', EntityType::class, [
                'class' => User::class,
                'query_builder' => function (UserRepository $er) {
                    return $er->createQueryBuilder('u')
                        ->where('u.role = :role')
                        ->setParameter('role', 'employee')
                        ->orderBy('u.firstName', 'ASC');
                },
                'choice_label' => function (User $user) {
                    return $user->getFirstName() . ' ' . $user->getLastName();
                },
                'label' => 'Ouvrier',
                'placeholder' => 'Sélectionnez un ouvrier',
            ])
            ->add('dateAssignment', DateTimeType::class, [
                'label' => 'Date d\'affectation',
                'widget' => 'single_text',
                'data' => new \DateTime(),
            ]);;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskAssignment::class,
        ]);
    }
}
