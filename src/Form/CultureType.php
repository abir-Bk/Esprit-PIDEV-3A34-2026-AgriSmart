<?php

namespace App\Form;

use App\Entity\Culture;
use App\Entity\Parcelle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints as Assert;

class CultureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('typeCulture', TextType::class, [
                'label' => 'Type de culture',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => 'Ex: Blé, Tomate, Maïs...'
                ]
            ])
            ->add('variete', TextType::class, [
                'label' => 'Variété',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'placeholder' => 'Ex: Spunta, Ambition...'
                ]
            ])
            ->add('datePlantation', DateType::class, [
                'label' => 'Date de plantation',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control rounded-3',
                    'max' => (new \DateTime())->format('Y-m-d')
                ]
            ])
            ->add('dateRecoltePrevue', DateType::class, [
                'label' => 'Date de récolte prévue',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control rounded-3'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    '🌱 En croissance' => 'En croissance',
                    '💧 Besoin d\'eau' => 'Besoin d\'eau',
                    '🌾 Mature' => 'Mature',
                    '📦 Récolté' => 'Récolté',
                    '⚠️ Maladie' => 'Maladie',
                    '🔬 Traitement' => 'Traitement'
                ],
                'attr' => [
                    'class' => 'form-select rounded-3'
                ]
            ])
            ->add('parcelle', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => 'nom',
                'attr' => [
                    'class' => 'form-select rounded-3'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Culture::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'culture_item'
        ]);
    }
}