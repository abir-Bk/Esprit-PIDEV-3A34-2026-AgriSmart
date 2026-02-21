<?php

namespace App\Form;

use App\Entity\SuiviTache;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SuiviTacheType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateTimeType::class, [
                'label' => 'Date du suivi',
                'widget' => 'single_text',
                'required' => false,
                'data' => new \DateTime(),
            ])
            ->add('rendement', TextType::class, [
                'label' => 'Rendement constaté',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: 500kg, 80%, etc.'],
            ])
            ->add('problemes', TextareaType::class, [
                'label' => 'Problèmes rencontrés',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('solution', TextareaType::class, [
                'label' => 'Solutions apportées / Recommandations',
                'required' => false,
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SuiviTache::class,
        ]);
    }
}
