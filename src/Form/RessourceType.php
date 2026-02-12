<?php

namespace App\Form;

use App\Entity\Ressource;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la ressource (ex: Nitrate, Blé...)',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le nom']
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices'  => [
                    'Engrais / Fertilisant' => 'Engrais',
                    'Semence / Graine' => 'Semence',
                    'Produit Phytosanitaire' => 'Phytosanitaire',
                    'Eau / Irrigation' => 'Eau',
                    'Carburant' => 'Carburant',
                    'Autre' => 'Autre',
                ],
                'attr' => ['class' => 'form-select'] // form-select est mieux pour Bootstrap 5
            ])
            ->add('unite', ChoiceType::class, [
                'label' => 'Unité de mesure',
                'choices'  => [
                    'Kilogrammes (kg)' => 'kg',
                    'Litres (L)' => 'L',
                    'Tonnes (t)' => 't',
                    'Unités / Pièces' => 'unités',
                    'Sacs' => 'sacs',
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('stockRestant', NumberType::class, [
                'label' => 'Quantité en stock',
                'attr' => ['class' => 'form-control', 'step' => '0.01', 'placeholder' => '0.00']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ressource::class,
        ]);
    }
}