<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Location;
use App\Validator\CompoundLocationConstraint;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompoundLocationType
    extends AbstractType
    implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'location',
                EntityType::class,
                [
                    'class' => 'App\Entity\Location',
                    'choice_label' => 'name',
                    'placeholder' => '',
                    'required' => false
                ]
            )
            ->add('newLocation', LocationType::class, [ 'required' => false ])
            ->setDataMapper($this)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Location::class,
            'compound' => true,
            'label' => '',
            'error_bubbling' => true,
            'constraints' => [ new CompoundLocationConstraint() ]
        ]);
    }

    public function mapDataToForms (mixed $viewData, \Traversable $forms) : void
    {
        if ( $viewData )
        {
            /** @var FormInterface[] $forms */
            $forms = iterator_to_array($forms);

            $location = $forms['location'];
            $newLocation = $forms['newLocation'];

            if ( $viewData->getId() )
                $location->setData($viewData);
            else
                $newLocation->setData($viewData);
        }
    }

    public function mapFormsToData (\Traversable $forms, mixed &$viewData) : void
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $locationFormData = $forms['location']->getData();
        $newLocationFormData = $forms['newLocation']->getData();

        if ( $locationFormData )
            $viewData = $locationFormData;
        else if (
            $newLocationFormData
            && (
                $newLocationFormData->getName()
                || $newLocationFormData->getAddress()
                || $newLocationFormData->getLatitude()
                || $newLocationFormData->getLongitude()
            )
        )
            $viewData = $newLocationFormData;
        else
            $viewData = null;
    }
}
