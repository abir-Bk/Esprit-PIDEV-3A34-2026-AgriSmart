<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['required' => true])
            ->add('categorie', ChoiceType::class, [
                'required' => true,
                'placeholder' => 'Choisir une catégorie',
                'choices' => [
                    'Légumes' => 'legumes',
                    'Fruits' => 'fruits',
                    'Céréales' => 'cereales',
                    'Engrais' => 'engrais',
                    'Semences' => 'semences',
                    'Matériel' => 'materiel',
                    'Services' => 'services',
                ],
            ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'placeholder' => 'Choisir',
                'choices' => [
                    'Vente' => 'vente',
                    'Location' => 'location',
                ],
            ])
            ->add('prix', MoneyType::class, [
                'required' => true,
                'currency' => 'TND',
            ])
            ->add('quantiteStock', IntegerType::class, [
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
            ])
            ->add('image', TextType::class, [
                'required' => false,
                'help' => 'URL optionnelle (sinon upload via champ fichier).',
            ])
            ->add('isPromotion')
            ->add('promotionPrice', MoneyType::class, [
                'required' => false,
                'currency' => 'TND',
            ])
            // Champs location (nullable dans l’Entity) :
            ->add('locationStart', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('locationEnd', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Produit::class]);
    }
}
