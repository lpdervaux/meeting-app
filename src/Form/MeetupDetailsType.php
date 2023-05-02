<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Meetup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeetupDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'userRegister',
                SubmitType::class,
                [
                    'label' => 'S\'inscrire',
                    'validate' => false
                ]
            )
            ->add(
                'userCancel',
                SubmitType::class,
                [
                    'label' => 'Se désister',
                    'validate' => false
                ]
            )
            ->add(
                'cancellationReason',
                TextareaType::class,
                [ 'label' => 'Raison d\'annulation' ]
            )
            ->add(
                'cancel',
                SubmitType::class,
                [ 'label' => 'Annuler' ]
            );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Meetup::class
        ]);
    }
}
