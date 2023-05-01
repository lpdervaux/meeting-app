<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Meetup;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateIntervalType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class MeetupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', options: [ 'label' => 'Nom'])
            ->add('description', options: [ 'label' => 'Description'])
            ->add('capacity',
                IntegerType::class,
                [
                    'attr' => [ 'min' => 5, 'max' => 50 ],
                    'label' => 'Capacité'
                ]
            )
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
                'duration',
                IntegerType::class,
                [
                    'attr' => [ 'min' => 1, 'max' => 12 ],
                    'getter' => fn (Meetup $meetup) : int => MeetupType::getDurationFromMeetup($meetup) ?? 1,
                    'setter' => fn (Meetup $meetup, int $value) => MeetupType::setEndFromDuration($meetup, $value),
                    'label' => 'Durée (heures)'
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

    static private function getDurationFromMeetup (Meetup $meetup) : ?int
    {
        if ( ! $meetup->getStart() || ! $meetup->getEnd() )
            $duration = null;
        else
            $duration = $meetup
                ->getStart()
                ->diff($meetup->getEnd())
                ->h;

        return $duration;
    }

    static private function setEndFromDuration (Meetup $meetup, int $duration) : void
    {
        if ( $meetup->getStart() )
        {
            $meetup->setEnd(
                $meetup->getStart()->add(new \DateInterval('PT' . $duration . 'H'))
            );
        }
    }
}
