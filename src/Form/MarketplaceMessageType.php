<?php

namespace App\Form;

use App\Entity\MarketplaceMessage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class MarketplaceMessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 3000,
                    'placeholder' => 'Écrivez votre message...',
                ],
            ])
            ->add('audioFile', FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => 'audio/*',
                    'class' => 'd-none',
                    'data-audio-file' => '1',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '8M',
                    ]),
                ],
            ])
            ->add('audioBlob', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-audio-blob' => '1',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MarketplaceMessage::class,
        ]);
    }
}
