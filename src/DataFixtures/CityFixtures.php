<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\City;
use Doctrine\Persistence\ObjectManager;

class CityFixtures extends FakerFixtures
{
    public const COUNT = 20;

    public function load(ObjectManager $manager): void
    {
        parent::load($manager);

        $this->fakeMany(self::COUNT);

        $this->manager->flush();
    }

    protected function generate () : City
    {
        return (new City())
            ->setName($this->generator->unique()->city())
            ->setPostalCode($this->generator->postcode());
    }
}
