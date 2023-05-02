<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', options: [ 'label' => 'Nom' ])
            ->add('address', options: [ 'label' => 'Adresse' ])
            ->add('latitude')
            ->add('longitude')
//            ->add(
//                'city',
//                EntityType::class,
//                [
//                    'class' => 'App\Entity\City',
//                    'choice_label' => 'name',
//                    'label' => 'Ville'
//                ]
//            )
            ->add(
                'city',
                CompoundCityType::class,
                [ 'label' => 'Ville' ]
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
        ]);
    }
}
