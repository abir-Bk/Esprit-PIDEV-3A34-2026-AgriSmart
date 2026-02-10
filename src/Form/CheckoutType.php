<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modePaiement', ChoiceType::class, [
                'label' => 'Mode de paiement',
                'choices' => [
                    'Paiement à domicile' => 'domicile',
                    'Carte bancaire (simulation)' => 'carte',
                ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note (optionnel)',
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => 'Ex: appeler avant de livrer...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
