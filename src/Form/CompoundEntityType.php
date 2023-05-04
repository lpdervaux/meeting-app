<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class CompoundEntityType
    extends AbstractType
    implements DataMapperInterface
{
    public const ENTITY_NULL = __CLASS__ . 'null';
    public const ENTITY_PARTIAL = __CLASS__ . 'partial';

    public const PATH_LIST = 'list';
    public const PATH_NEW = 'new';

    public const NAME_LIST = 'list';
    public const NAME_NEW = 'new';

    protected const NULL_MESSAGE = 'Please select a value';

    public function buildForm (FormBuilderInterface $builder, array $options) : void
    {
         $builder->setDataMapper($this);
    }

    public function configureOptions (OptionsResolver $resolver) : void
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => '',
            'constraints' => [
                new Callback(fn (...$arguments) => $this->validate(...$arguments))
            ]
        ]);
    }

    protected function getListOptions () : array
    {
        return [
            'placeholder' => '',
            'required' => false,
            'property_path' => $this::PATH_LIST
        ];
    }

    protected function getNewOptions () : array
    {
        return [
            'required' => false,
            'property_path' => $this::PATH_NEW,
            'validation_groups' => fn (...$arguments) => $this::validationGroups(...$arguments)
        ];
    }

    public function mapDataToForms (mixed $viewData, \Traversable $forms) : void
    {
        if ( $viewData )
        {
            /** @var FormInterface[] $forms */
            $forms = iterator_to_array($forms);

            $listForm = $forms[$this::NAME_LIST];
            $newForm = $forms[$this::NAME_NEW];

            if ( $viewData->getId() )
                $listForm->setData($viewData);
            else
                $newForm->setData($viewData);
        }
    }

    public function mapFormsToData (\Traversable $forms, mixed &$viewData) : void
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $listData = $forms[$this::NAME_LIST]->getData();
        $newData = $forms[$this::NAME_NEW]->getData();

        if ( $listData )
            $viewData = $listData;
        else if (
            $newData && $this->isPartial($newData)
        )
            $viewData = $newData;
        else
            $viewData = null;
    }

    protected function validate (mixed $data, ExecutionContextInterface $context) : void
    {
        if ( ! $data )
            $context
                ->buildViolation($this::NULL_MESSAGE)
                ->setCode($this::ENTITY_NULL)
                ->atPath($this::PATH_LIST)
                ->addViolation();
        else
        {
            $violations = $context->getValidator()->validate($data);

            if ( $violations->count() > 0 )
                foreach ( $violations as $violation )
                {
                    $context
                        ->buildViolation($violation->getMessage())
                        ->setInvalidValue($violation->getInvalidValue())
                        ->setCode($this::ENTITY_PARTIAL)
                        ->atPath($this::PATH_NEW . '.' . $violation->getPropertyPath())
                        ->addViolation();
                }
        }
    }

    protected function validationGroups (FormInterface $form) : array
    {
        if (
            ( $form->getParent()->getData()?->getId() )
            || ( ! $form->getData() )
        )
            $groups = [];
        else
            $groups = [ 'Default' ];

        return $groups;
    }

    abstract protected function isPartial (mixed $entity) : bool;
}
