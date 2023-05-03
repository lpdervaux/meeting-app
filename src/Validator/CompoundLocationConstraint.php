<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

class CompoundLocationConstraint extends Constraint {
    public const NULL_ENTITY_CODE = __CLASS__ . 'null';
    public const PARTIAL_ENTITY_CODE = __CLASS__ . 'partial';
}