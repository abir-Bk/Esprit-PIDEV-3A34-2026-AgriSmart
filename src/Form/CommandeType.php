<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Paiement à domicile' => 'domicile',
                    'Paiement par carte' => 'carte',
                ],
                'placeholder' => 'Choisir...',
                'required' => true,
                'attr' => ['class' => 'form-select'],
            ])
            ->add('adresseLivraison', TextareaType::class, [
                'label' => 'Adresse de livraison (si domicile)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'form-control',
                    'placeholder' => 'Ville, rue, détails...',
                ],
                'help' => 'Obligatoire uniquement si paiement à domicile.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
