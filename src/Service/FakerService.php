<?php

declare(strict_types=1);

namespace App\Service;

use Faker\Factory;
use Faker\Generator;

class FakerService
{
    private readonly Generator $generator;

    public function __construct ()
    {
        $this->generator = Factory::create(
            locale_compose(
                ['language' => 'fr', 'region' => 'FR']
            )
        );
    }

    public function getGenerator () : Generator
    {
        return $this->generator;
    }
}