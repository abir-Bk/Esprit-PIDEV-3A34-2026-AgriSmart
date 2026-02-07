<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class)
            ->add('categorie', ChoiceType::class, [
                'placeholder' => 'Choisir...',
                'choices' => [
                    'Légumes' => 'legumes',
                    'Fruits' => 'fruits',
                    'Céréales' => 'cereales',
                    'Engrais' => 'engrais',
                    'Semences' => 'semences',
                    'Matériel agricole' => 'materiel',
                    'Services' => 'services',
                    'Autre' => 'autre',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'placeholder' => 'Choisir...',
                'choices' => [
                    'Vente' => 'vente',
                    'Location' => 'location',
                ],
            ])
            ->add('prix', NumberType::class)
            ->add('quantiteStock', NumberType::class)
            ->add('image', TextType::class, [
                'required' => false, // URL optionnelle
            ])
            ->add('imageFile', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('isPromotion', CheckboxType::class, [
                'required' => false,
            ])
            ->add('promotionPrice', NumberType::class, [
                'required' => false,
            ])
            // LOCATION (only used when type=location, we hide/show via JS)
            ->add('locationStart', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('locationEnd', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('locationAddress', TextType::class, [
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
