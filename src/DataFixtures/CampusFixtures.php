<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends FakerFixtures
{
    public const COUNT = 5;

    public function load (ObjectManager $manager) : void
    {
        parent::load($manager);

        $this->fakeMany(self::COUNT);

        $manager->flush();
    }

    protected function generate () : Campus
    {
        return (new Campus())
            ->setName($this->generator->unique()->city());
    }
}
