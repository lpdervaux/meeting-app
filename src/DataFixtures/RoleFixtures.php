<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public const USER_REFERENCE = 'user-role';
    public const ADMINISTRATOR_REFERENCE = 'administrator-role';

    public function load(ObjectManager $manager): void
    {
        $userRole = (new Role())
            ->setRole('ROLE_USER')
            ->setLabel('User')
            ->setDescription('Regular user role');
        $manager->persist($userRole);

        $administratorRole = (new Role())
            ->setRole('ROLE_ADMINISTRATOR')
            ->setLabel('Administrator')
            ->setDescription('Administrator role');
        $manager->persist($administratorRole);

        $manager->flush();

        $this->addReference(self::USER_REFERENCE, $userRole);
        $this->addReference(self::ADMINISTRATOR_REFERENCE, $administratorRole);
    }
}

