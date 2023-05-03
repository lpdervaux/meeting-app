<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\City;
use App\Validator\CompoundCityConstraint;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompoundCityType
    extends AbstractType
    implements DataMapperInterface
{
    public const LIST_PROPERTY_PATH = 'city';
    public const NEW_PROPERTY_PATH = 'new_city';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'city',
                EntityType::class,
                [
                    'class' => 'App\Entity\City',
                    'choice_label' => 'name',
                    'placeholder' => '',
                    'required' => false,
                    'property_path' => self::LIST_PROPERTY_PATH
                ]
            )
            ->add(
                'newCity',
                CityType::class,
                [
                    'required' => false,
                    'property_path' => self::NEW_PROPERTY_PATH
                ]
            )
            ->setDataMapper($this);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => City::class,
            'compound' => true,
            'label' => '',
            'error_bubbling' => true,
            'constraints' => [ new CompoundCityConstraint() ]
        ]);
    }

    public function mapDataToForms (mixed $viewData, \Traversable $forms) : void
    {
        if ( $viewData )
        {
            /** @var FormInterface[] $forms */
            $forms = iterator_to_array($forms);

            $city = $forms['city'];
            $newCity = $forms['newCity'];

            if ( $viewData->getId() )
                $city->setData($viewData);
            else
                $newCity->setData($viewData);
        }
    }

    public function mapFormsToData (\Traversable $forms, mixed &$viewData) : void
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $cityData = $forms['city']->getData();
        $newCityData = $forms['newCity']->getData();

        if ( $cityData )
            $viewData = $cityData;
        else if (
            $newCityData
            && (
                $newCityData->getName()
                || $newCityData->getPostalCode()
            )
        )
            $viewData = $newCityData;
        else
            $viewData = null;
    }
}
