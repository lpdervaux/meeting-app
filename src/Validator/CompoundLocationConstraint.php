<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CompoundLocationConstraint extends Constraint
{
    public string $message = "Invalid location";
}