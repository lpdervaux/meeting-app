<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompoundLocationType extends CompoundEntityType
{
    public const ENTITY_NULL = __CLASS__ . 'null';
    public const ENTITY_PARTIAL = __CLASS__ . 'partial';

    protected const NULL_MESSAGE = 'Please select a location';

    public function buildForm (FormBuilderInterface $builder, array $options) : void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add(
                $this::NAME_LIST,
                EntityType::class,
                [
                    'class' => 'App\Entity\Location',
                    'choice_label' => 'name',
                    ... $this->getListOptions()
                ]
            )
            ->add(
                $this::NAME_NEW,
                LocationType::class,
                [ ... $this->getNewOptions() ]
            );
    }

    public function configureOptions (OptionsResolver $resolver) : void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([ 'data_class' => Location::class ]);
    }

    protected function isPartial (mixed $entity) : bool
    {
        return ( $entity->getName() )
            || ( $entity->getAddress() )
            || ( $entity->getLatitude() )
            || ( $entity->getLongitude() )
            || ( $entity->getCity() );
    }
}