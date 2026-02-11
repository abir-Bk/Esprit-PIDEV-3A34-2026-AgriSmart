<?php

namespace App\Form;

use App\Entity\Consommation;
use App\Entity\Culture;
use App\Entity\Ressource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConsommationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite')
            ->add('dateConsommation', null, [
                'widget' => 'single_text',
            ])
            ->add('ressource', EntityType::class, [
                'class' => Ressource::class,
                'choice_label' => 'id',
            ])
            ->add('culture', EntityType::class, [
                'class' => Culture::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Consommation::class,
        ]);
    }
}
