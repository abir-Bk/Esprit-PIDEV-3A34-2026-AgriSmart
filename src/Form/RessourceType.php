<?php

namespace App\Form;

use App\Entity\Ressource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la ressource',
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => 'Ex: Engrais NPK']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices'  => [
                    'Eau' => 'Eau',
                    'Engrais' => 'Engrais',
                    'Semence' => 'Semence',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select rounded-3']
            ])
            ->add('stockRestan', NumberType::class, [ // Correspond au getter getStockRestan()
                'label' => 'Quantité en stock',
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => '0.00']
            ])
            ->add('unite', TextType::class, [
                'label' => 'Unité',
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => 'Litres, Kg...']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Ressource::class]);
    }
}