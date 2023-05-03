<?php

declare(strict_types=1);

namespace App\Validator;

use App\Form\CompoundLocationType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CompoundLocationConstraintValidator extends ConstraintValidator
{
    public function validate (mixed $value, Constraint $constraint) : void
    {
        if ( ! $value )
            $this->context
                ->buildViolation('Please select a valid location')
                ->setCode(CompoundLocationConstraint::NULL_ENTITY_CODE)
                ->atPath(CompoundLocationType::LIST_PROPERTY_PATH)
                ->addViolation();
        else
        {
            $violations = $this->context->getValidator()->validate($value);

            if ( $violations->count() > 0 )
                foreach ( $violations as $violation )
                {
                    $this->context
                        ->buildViolation($violation->getMessage())
                        ->setInvalidValue($violation->getInvalidValue())
                        ->setCode(CompoundLocationConstraint::PARTIAL_ENTITY_CODE)
                        ->atPath(CompoundLocationType::NEW_PROPERTY_PATH . '.' . $violation->getPropertyPath())
                        ->addViolation();
                }
        }
    }
}