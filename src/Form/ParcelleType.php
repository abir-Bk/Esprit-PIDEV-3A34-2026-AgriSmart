<?php

namespace App\Form;

use App\Entity\Parcelle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ParcelleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la parcelle',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => 'Ex: Parcelle Nord, Champ Sud...'
                ]
            ])
            ->add('surface', NumberType::class, [
                'label' => 'Surface (hectares)',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => 'Ex: 2.5',
                    'step' => '0.01'
                ]
            ])
            ->add('typeSol', ChoiceType::class, [
                'label' => 'Type de sol',
                'choices' => [
                    'Argileux' => 'Argileux',
                    'Sableux' => 'Sableux',
                    'Limoneux' => 'Limoneux',
                    'Calcaire' => 'Calcaire',
                    'Tourbeux' => 'Tourbeux',
                    'Humifère' => 'Humifère'
                ],
                'attr' => ['class' => 'form-select rounded-3'],
                'placeholder' => 'Sélectionnez un type de sol'
            ])
            ->add('latitude', NumberType::class, [
                'label' => 'Latitude',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => 'Ex: 36.899',
                    'step' => '0.000001',
                    'readonly' => true,
                    'id' => 'parcelle_latitude'
                ]
            ])
            ->add('longitude', NumberType::class, [
                'label' => 'Longitude',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => 'Ex: 10.189',
                    'step' => '0.000001',
                    'readonly' => true,
                    'id' => 'parcelle_longitude'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Parcelle::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'parcelle_item'
        ]);
    }
}