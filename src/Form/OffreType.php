<?php
namespace App\Form;

use App\Entity\Offre;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OffreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('typePoste')
            ->add('typeContrat', ChoiceType::class, [
                'choices' => ['CDI' => 'CDI', 'CDD' => 'CDD',  'Stage' => 'Stage']
            ])
            ->add('description', TextareaType::class, ['attr' => ['rows' => 5]])
            ->add('lieu')
            ->add('salaire', NumberType::class)
            ->add('statut', ChoiceType::class, [
                'choices' => ['Ouvert' => 'Ouvert', 'Clôturée' => 'Clôturée']
            ])
            ->add('dateDebut', DateType::class, ['widget' => 'single_text'])
            ->add('dateFin', DateType::class, ['widget' => 'single_text']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Offre::class]);
    }
}