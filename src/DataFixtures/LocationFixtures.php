<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures
    extends FakerFixtures
    implements DependentFixtureInterface
{
    public const COUNT = 40;

    public function getDependencies () : array
    {
        return [ CityFixtures::class ];
    }

    public function load (ObjectManager $manager) : void
    {
        parent::load($manager);

        $this->fakeMany(self::COUNT);

        $manager->flush();
    }

    protected function generate () : Location
    {
        return (new Location())
            ->setName(
                $this->generator->unique()->company()
            )
            ->setAddress(
                $this->generator->address()
            )
            ->setCity(
                $this->getReference(CityFixtures::class . mt_rand(0, CityFixtures::COUNT - 1))
            )
            ->setLatitude(
                $this->generator->latitude()
            )
            ->setLongitude(
                $this->generator->longitude()
            );
    }
}
