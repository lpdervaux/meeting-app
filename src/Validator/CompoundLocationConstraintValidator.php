<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CompoundLocationConstraintValidator extends ConstraintValidator
{
    public function validate (mixed $value, Constraint $constraint) : void
    {
        if ( ! $value )
            $this->context
                ->buildViolation('Please select a valid location')
                ->atPath('location')
                ->addViolation();
        else
        {
            $violations = $this->context->getValidator()->validate($value);

            if ( $violations->count() > 0 )
                $this->context
                    ->buildViolation('Please fill new location information.')
                    ->atPath('location') // TODO: find how to map the error to the correct fields
                    ->addViolation();
        }
    }
}