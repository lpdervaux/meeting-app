<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Location;
use App\Service\FakerService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture implements DependentFixtureInterface
{
    public const LOCATION_COUNT = 40;
    public const LOCATION_PREFIX = 'location_';

    private readonly ObjectManager $manager;
    private readonly array $cities;

    public function __construct (
        private readonly FakerService $fakerService
    ) {}

    public function getDependencies () : array
    {
        return [CityFixtures::class];
    }

    public function load (ObjectManager $manager) : void
    {
        $this->initialize($manager);

        $this->fakeMany(self::LOCATION_COUNT);

        $manager->flush();
    }

    private function initialize (ObjectManager $manager) : void
    {
        $this->manager = $manager;

        $cities = [];
        for ($i = 0; $i < CityFixtures::COUNT; $i++) {
            $cities[] = $this->getReference(CityFixtures::class . $i);
        }
        $this->cities = $cities;
    }

    private function fakeMany (int $count) : void
    {
        for ($i = 0; $i < $count; $i++) {
            $location = $this->generateFake();
            $this->manager->persist($location);
            $this->addReference(self::LOCATION_PREFIX . $i, $location);
        }
    }

    private function generateFake () : Location
    {
        return (new Location())
            ->setName(
                $this->fakerService->getGenerator()->unique()->company()
            )
            ->setAddress(
                $this->fakerService->getGenerator()->address()
            )
            ->setCity(
                $this->fakerService->getGenerator()->randomElement($this->cities)
            )
            ->setLatitude(
                $this->fakerService->getGenerator()->latitude()
            )
            ->setLongitude(
                $this->fakerService->getGenerator()->longitude()
            );
    }
}
