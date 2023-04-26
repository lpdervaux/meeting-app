<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Service\FakerService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

abstract class FakerFixtures extends Fixture
{
    protected readonly ObjectManager $manager;
    protected readonly Generator $generator;

    public function __construct (
        private readonly FakerService $fakerService
    ) {
        $this->generator = $this->fakerService->getGenerator();
        $this->generator->unique(true);
    }

    private function initialize (ObjectManager $manager) : void
    {
        $this->manager = $manager;
    }

    public function load (ObjectManager $manager) : void
    {
        $this->initialize($manager);
    }

    abstract protected function generate () : mixed;

    protected function fakeMany (int $count, ?string $prefix = null, ?callable $entityGenerator = null) : void
    {
        for ($i = 0; $i < $count; $i++) {
            $entity = ( $entityGenerator ) ? $entityGenerator() : $this->generate();
            $this->manager->persist($entity);
            $this->addReference(
                ( $prefix ) ? $prefix . $i : $this::class . $i,
                $entity
            );
        }
    }
}