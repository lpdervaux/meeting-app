<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Role;
use App\Entity\User;
use App\Service\FakerService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const DEFAULT_USER = 'user_default';
    public const DEFAULT_ADMINISTRATOR = 'administrator_default';
    public const USER_COUNT = 3000;
    public const USER_PREFIX = 'user_';
    public const ADMINISTRATOR_COUNT = 30;
    public const ADMINISTRATOR_PREFIX = 'administrator_';

    private const DOMAIN = 'campus-eni.fr';

    // initialized on load()
    private readonly array $campuses;
    private readonly ObjectManager $manager;

    // used to keep track of nickname collisions
    private array $nicknameOffset = [];

    public function __construct (
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly FakerService $fakerService
    ) {}

    public function getDependencies () : array
    {
        return [
            RoleFixtures::class,
            CampusFixtures::class
        ];
    }

    public function load (ObjectManager $manager) : void
    {
        $this->initialize($manager);

        $userRole = $this->getReference(RoleFixtures::USER);
        $administratorRole = $this->getReference(RoleFixtures::ADMINISTRATOR);

        $this->createDefault(
            'User',
            $userRole,
            $this->getReference(CampusFixtures::ONLINE),
            self::DEFAULT_USER
        );

        $this->createDefault(
            'Administrator',
            $administratorRole,
            $this->getReference(CampusFixtures::ONLINE),
            self::DEFAULT_ADMINISTRATOR
        );

        $this->createFakes(
            $userRole,
            self::USER_COUNT,
            self::USER_PREFIX
        );

        $this->createFakes(
            $administratorRole,
            self::ADMINISTRATOR_COUNT,
            self::ADMINISTRATOR_PREFIX
        );

        $manager->flush();
    }

    private function initialize (ObjectManager $manager) : void
    {
        $this->manager = $manager;
        $this->campuses = [
            $this->getReference(CampusFixtures::SAINT_HERBLAIN),
            $this->getReference(CampusFixtures::CHARTRES_DE_BRETAGNE),
            $this->getReference(CampusFixtures::LA_ROCHE_SUR_YON),
            $this->getReference(CampusFixtures::ONLINE)
        ];
    }

    private function createDefault (
        string $name,
        Role $role,
        Campus $campus,
        string $reference
    ) : void
    {
        $user = $this->generateDefault($name, $role, $campus);
        $this->manager->persist($user);
        $this->addReference($reference, $user);
    }

    private function generateDefault (string $name, Role $role, Campus $campus) : User
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

    // generates a unique nickname
    private function generateNickname (string $name, string $surname) : string
    {
        $id = mb_strtolower($name) . '.' . mb_strtolower($surname);
        $offset = $this->nicknameOffset[$id] ?? null;

        if ( $offset === null ) {
            $nickname = $id;
            $this->nicknameOffset[$id] = 2;
        } else {
            $nickname = $id . '.' . $offset;
            $this->nicknameOffset[$id] = $offset + 1;
        }

        return $nickname;
    }

    private function generateFake (Role $role) : User
    {
        $name = $this->fakerService->getGenerator()->firstName();
        $surname = $this->fakerService->getGenerator()->lastName();
        $nickname = $this->generateNickname($name, $surname);
        $year = date('Y') - rand(1, 3);

        $user = new User();
        $user
            ->setEmail($nickname . '.' . $year . '@' . self::DOMAIN)
            ->setNickname($nickname)
            ->setPassword(
                $this->userPasswordHasher->hashPassword($user, $nickname)
            )
            ->setName($name)
            ->setSurname($surname)
            ->setPhoneNumber($this->fakerService->getGenerator()->phoneNumber())
            ->setCampus($this->campuses[array_rand($this->campuses)])
            ->addRole($role);

        return $user;
    }

    private function createFakes (
        Role $role,
        int $count,
        string $prefix
    ) : void
    {
        for ($i = 0; $i < $count; $i++)
        {
            $user = $this->generateFake($role);
            $this->manager->persist($user);
            $this->addReference($prefix . $i, $user);
        }
    }
}
