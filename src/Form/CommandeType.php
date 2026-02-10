<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $maxStock = (int) ($options['max_stock'] ?? 1);

        $builder
            ->add('quantite', IntegerType::class, [
                'label' => 'Quantité',
                'mapped' => false,            // ✅ on gère nous-mêmes
                'data' => 1,
                'attr' => [
                    'min' => 1,
                    'max' => max(1, $maxStock),
                ],
            ])
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Paiement à la livraison' => 'cod',
                    'Carte bancaire (bientôt)' => 'card',
                ],
                'placeholder' => 'Choisir...',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            'max_stock' => 1, // option custom
        ]);

        $resolver->setAllowedTypes('max_stock', 'int');
    }
}
