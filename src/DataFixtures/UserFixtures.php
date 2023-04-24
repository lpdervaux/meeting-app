<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_REFERENCE = 'user';
    public const ADMINISTRATOR_REFERENCE = 'administrator';

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
        $user = new User();
        $user
            ->setEmail('user@example.org')
            ->setNickname('User')
            ->setPassword(
                $this->userPasswordHasher->hashPassword($user, 'user')
            )
            ->setName('User-name')
            ->setSurname('User-surname')
            ->setPhoneNumber('0102030405')
            ->setCampus(
                $this->getReference(CampusFixtures::ONLINE_CAMPUS_REFERENCE)
            )
            ->addRole(
                $this->getReference(RoleFixtures::USER_REFERENCE)
            );
        $manager->persist($user);

        $administrator = new User();
        $administrator
            ->setEmail('administrator@example.org')
            ->setNickname('Administrator')
            ->setPassword(
                $this->userPasswordHasher->hashPassword($administrator, 'administrator')
            )
            ->setName('Administrator-name')
            ->setSurname('Administrator-surname')
            ->setPhoneNumber('0607080900')
            ->setCampus(
                $this->getReference(CampusFixtures::ONLINE_CAMPUS_REFERENCE)
            )
            ->addRole(
                $this->getReference(RoleFixtures::ADMINISTRATOR_REFERENCE)
            );
        $manager->persist($administrator);

        $manager->flush();

        $this->addReference(self::USER_REFERENCE, $user);
        $this->addReference(self::ADMINISTRATOR_REFERENCE, $administrator);
    }
}
