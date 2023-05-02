<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class CompoundLocationConstraintValidator extends ConstraintValidator
{
    public function validate (mixed $value, Constraint $constraint) : void
    {
        $violations = $this->context->getValidator()->validate($value);

        if ( $violations->count() > 0 )
            $this->context
                ->buildViolation('Invalid location')
                ->atPath('location')
                ->addViolation();
    }
}