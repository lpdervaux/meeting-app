<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public const USER = __CLASS__ . 'user';
    public const ADMINISTRATOR = __CLASS__ . 'administrator';

    public function load(ObjectManager $manager): void
    {
        $userRole = (new Role())
            ->setRole('ROLE_USER')
            ->setLabel('User')
            ->setDescription('Regular user role');
        $manager->persist($userRole);
        $this->addReference(self::USER, $userRole);

        $administratorRole = (new Role())
            ->setRole('ROLE_ADMINISTRATOR')
            ->setLabel('Administrator')
            ->setDescription('Administrator role');
        $manager->persist($administratorRole);
        $this->addReference(self::ADMINISTRATOR, $administratorRole);

        $manager->flush();
    }
}

