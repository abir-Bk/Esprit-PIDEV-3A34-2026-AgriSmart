<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
            ->add('nom', TextType::class, [
                'label' => "Nom de l'annonce",
                'attr' => [
                    'class' => 'form-control am-input js-sync-name',
                    'placeholder' => 'Ex: Tracteur, Panier de légumes...',
                ],
            ])

            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie',
                'placeholder' => 'Choisir...',
                'choices' => Produit::CATEGORIES, // ✅ réutilise tes constantes
                'attr' => [
                    'class' => 'form-select am-input js-sync-cat',
                ],
            ])

            ->add('type', ChoiceType::class, [
                'label' => "Type d'offre",
                'placeholder' => 'Choisir...',
                'choices' => [
                    'Vente' => Produit::TYPE_VENTE,
                    'Location' => Produit::TYPE_LOCATION,
                ],
                'attr' => [
                    'class' => 'form-select am-input js-type-selector',
                ],
            ])

            ->add('prix', NumberType::class, [
                'label' => 'Prix (TND)',
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control am-input js-sync-price',
                    'placeholder' => '0.00',
                    'min' => 0,
                    'step' => '0.01',
                ],
            ])

            ->add('quantiteStock', NumberType::class, [
                'label' => 'Stock disponible',
                'attr' => [
                    'class' => 'form-control am-input js-sync-stock',
                    'min' => 0,
                    'step' => '1',
                ],
            ])

            // ✅ Ville/Région obligatoire pour vente + location (norme marketplace)
            ->add('locationAddress', TextType::class, [
                'label' => 'Ville / Région',
                'required' => true,
                'attr' => [
                    'class' => 'form-control am-input js-sync-loc',
                    'placeholder' => 'Ex: Béja, Tunisie',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control am-input js-sync-desc',
                    'rows' => 4,
                    'placeholder' => 'Décrivez votre produit...',
                ],
            ])

            ->add('isPromotion', CheckboxType::class, [
                'label' => 'Activer un prix promotionnel',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input js-promo-switch',
                ],
            ])

            ->add('promotionPrice', NumberType::class, [
                'label' => 'Prix promo (TND)',
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control am-input border-warning js-sync-promo-price',
                    'placeholder' => '0.00',
                    'min' => 0,
                    'step' => '0.01',
                ],
            ])

            // ✅ Dates uniquement pour location (affiché/activé via JS dans _form)
            ->add('locationStart', DateType::class, [
                'label' => 'Disponible du',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control am-input js-loc-start',
                ],
            ])

            ->add('locationEnd', DateType::class, [
                'label' => 'Au',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control am-input js-loc-end',
                ],
            ])

            // Upload file (non mappé)
            ->add('imageFile', FileType::class, [
                'label' => 'Image du produit',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'form-control am-input js-image-file',
                    'accept' => 'image/*',
                ],
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
