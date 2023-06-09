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
use Symfony\Component\String\Slugger\AsciiSlugger;

class UserFixtures
    extends FakerFixtures
    implements DependentFixtureInterface
{
    public const DEFAULT_USER = __CLASS__ . 'default-user';
    public const DEFAULT_ADMINISTRATOR = __CLASS__ . 'default-administrator';

    public const COUNT = 1000;
    public const ADMINISTRATOR_COUNT = 10;
    public const ADMINISTRATOR_PREFIX = __CLASS__ . 'administrator';

    private readonly AsciiSlugger $asciiSlugger;

    public function __construct (
        FakerService $fakerService,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
    ) {
        parent::__construct($fakerService);
        $this->asciiSlugger = new AsciiSlugger();
    }

    public function getDependencies () : array
    {
        return [
            RoleFixtures::class,
            CampusFixtures::class
        ];
    }

    public function load (ObjectManager $manager) : void
    {
        parent::load($manager);

        $administratorRole = $this->getReference(RoleFixtures::ADMINISTRATOR);

        $defaultUser = $this->generate(nickname: 'user', email: 'user@example.org');
        $manager->persist($defaultUser);
        $this->addReference(self::DEFAULT_USER, $defaultUser);

        $defaultAdministrator = $this->generate(nickname: 'administrator', email: 'administrator@example.org', roles: [ $administratorRole ]);
        $manager->persist($defaultAdministrator);
        $this->addReference(self::DEFAULT_ADMINISTRATOR, $defaultAdministrator);

        $this->fakeMany(self::COUNT);
        $this->fakeMany(
            self::ADMINISTRATOR_COUNT,
            self::ADMINISTRATOR_PREFIX,
            fn () => $this->generate(roles: [ $administratorRole ])
        );

        $manager->flush();
    }

    protected function generate (
        ?string $name = null,
        ?string $surname = null,
        ?string $nickname = null,
        ?string $email = null,
        ?array $roles = null
    ) : User
    {
        $name ??= $this->generator->firstName();
        $surname ??= $this->generator->lastName();
        $nickname ??= $this->generateUniqueNickname($name, $surname);
        $email ??= $nickname . '@' . $this->generator->safeEmailDomain();

        $user = new User();
        $user
            ->setEmail($email)
            ->setNickname($nickname)
            ->setPassword($this->userPasswordHasher->hashPassword($user, $nickname))
            ->setName($name)
            ->setSurname($surname)
            ->setPhoneNumber($this->generator->phoneNumber())
            ->setCampus($this->getReference(CampusFixtures::class . mt_rand(0, CampusFixtures::COUNT - 1)));

        if ( $roles )
            foreach ( $roles as $role ) $user->addRole($role);
        else
            $user->addRole($this->getReference(RoleFixtures::USER));

        return $user;
    }

    private array $nicknameOffset = [];

    private function generateUniqueNickname (string $name, string $surname) : string
    {
        $identifier =
            $this->asciiSlugger->slug(mb_strtolower($name))
            . '.'
            . $this->asciiSlugger->slug(mb_strtolower($surname));
        $offset = $this->nicknameOffset[$identifier] ?? null;

        if ( $offset === null )
        {
            $nickname = $identifier;
            $this->nicknameOffset[$identifier] = 2;
        }
        else
        {
            $nickname = $identifier . '.' . $offset;
            $this->nicknameOffset[$identifier] = $offset + 1;
        }

        return $nickname;
    }
}
