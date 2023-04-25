<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\City;
use App\Service\FakerService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CityFixtures extends Fixture
{
    public const CITY_COUNT = 20;
    public const CITY_PREFIX = 'city_';

    private readonly ObjectManager $manager;

    public function __construct (
        private readonly FakerService $fakerService
    ) {}

    public function load(ObjectManager $manager): void
    {
        $this->initialize($manager);

        $this->fakeMany(self::CITY_COUNT);

        $manager->flush();
    }

    private function initialize (ObjectManager $manager) : void
    {
        $this->manager = $manager;
    }

    private function fakeMany (int $count) : void
    {
        for ($i = 0; $i < $count; $i++) {
            $city = $this->generateFake();
            $this->manager->persist($city);
            $this->addReference(self::CITY_PREFIX . $i, $city);
        }
    }

    private function generateFake () : City
    {
        return (new City())
            ->setName(
                $this->fakerService->getGenerator()->unique()->city()
            )
            ->setPostalCode(
                $this->fakerService->getGenerator()->postcode()
            );
    }
}
