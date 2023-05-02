<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CompoundCityConstraintValidator extends ConstraintValidator
{
    public function validate (mixed $value, Constraint $constraint) : void
    {
        if ( ! $value )
            $this->context
                ->buildViolation('Please select a valid city')
                ->atPath('city')
                ->addViolation();
        else
        {
            $violations = $this->context->getValidator()->validate($value);

            if ( $violations->count() > 0 )
                $this->context
                    ->buildViolation('Please fill new city information.')
                    ->atPath('city') // TODO: find how to map the error to the correct fields
                    ->addViolation();
        }
    }
}