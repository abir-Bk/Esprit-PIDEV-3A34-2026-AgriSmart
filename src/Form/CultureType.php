<?php

namespace App\Form;

use App\Entity\Culture;
use App\Entity\Parcelle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CultureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeCulture')
            ->add('variete')
            ->add('datePlantation')
            ->add('dateRecoltePrevue')
            ->add('statut')
            ->add('parcelle', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Culture::class,
        ]);
    }
}
