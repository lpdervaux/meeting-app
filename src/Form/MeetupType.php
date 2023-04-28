<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Meetup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', options: [ 'label' => 'Nom'])
            ->add('description', options: [ 'label' => 'Description'])
            ->add('capacity', options: [ 'label' => 'Capacité'])
            ->add(
                'campus',
                EntityType::class,
                [
                    'class' => 'App\Entity\Campus',
                    'choice_label' => 'name',
                    'label' => 'Campus'
                ]
            )
            ->add(
                'location',
                EntityType::class,
                [
                    'class' => 'App\Entity\Location',
                    'choice_label' => 'name',
                    'label' => 'Lieu'
                ]
            )
            ->add(
                'registrationStart',
                options: [
                    'widget' => 'single_text',
                    'label' => 'Ouverture des inscriptions'
                ]
            )
            ->add(
                'registrationEnd',
                options: [
                    'widget' => 'single_text',
                    'label' => 'Clôture des inscriptions'
                ]
            )
            ->add(
                'start',
                options: [
                    'widget' => 'single_text',
                    'label' => 'Début de sortie'
                ]
            )
            ->add(
                'end',
                options: [
                    'widget' => 'single_text',
                    'label' => 'Fin de sortie'
                ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Meetup::class,
        ]);
    }
}
