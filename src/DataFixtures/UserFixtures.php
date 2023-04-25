<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_REFERENCE = 'user';
    public const ADMINISTRATOR_REFERENCE = 'administrator';

    private const DOMAIN = 'campus-eni.fr';

    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher
    ) {}

    public function getDependencies() : array
    {
        return [
            RoleFixtures::class,
            CampusFixtures::class
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->createDefaultUser(
            'User',
            $this->getReference(RoleFixtures::USER_REFERENCE),
            $this->getReference(CampusFixtures::ONLINE_CAMPUS_REFERENCE)
        );
        $manager->persist($user);
        $this->addReference(self::USER_REFERENCE, $user);

        $administrator = $this->createDefaultUser(
            'Administrator',
            $this->getReference(RoleFixtures::USER_REFERENCE),
            $this->getReference(CampusFixtures::ONLINE_CAMPUS_REFERENCE)
        );
        $manager->flush();
        $this->addReference(self::ADMINISTRATOR_REFERENCE, $administrator);
    }

    private function createDefaultUser (string $name, Role $role, Campus $campus) : User
    {
        $lowerName = mb_strtolower($name);

        $user = new User();
        $user
            ->setEmail($lowerName . '@' . self::DOMAIN)
            ->setNickname($name)
            ->setPassword(
                $this->userPasswordHasher->hashPassword($user, $lowerName)
            )
            ->setName($name . '-name')
            ->setSurname($name . '-surname')
            ->setPhoneNumber('0102030405')
            ->setCampus($campus)
            ->addRole($role);

        return $user;
    }
}
