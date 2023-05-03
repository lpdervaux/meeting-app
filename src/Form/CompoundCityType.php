<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\City;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompoundCityType extends CompoundEntityType
{
    public const ENTITY_NULL = __CLASS__ . 'null';
    public const ENTITY_PARTIAL = __CLASS__ . 'partial';

    protected const NULL_MESSAGE = 'Please select a city';

    public function buildForm (FormBuilderInterface $builder, array $options) : void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                $this::NAME_LIST,
                EntityType::class,
                [
                    'class' => 'App\Entity\City',
                    'choice_label' => 'name',
                    ... $this->getListOptions()
                ]
            )
            ->add(
                $this::NAME_NEW,
                CityType::class,
                [ ... $this->getNewOptions() ]
            );
    }

    public function configureOptions (OptionsResolver $resolver) : void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([ 'data_class' => City::class ]);

    }

    protected function isPartial (mixed $entity) : bool
    {
        return ( $entity->getName() )
            || ( $entity->getPostalCode() );
    }
}