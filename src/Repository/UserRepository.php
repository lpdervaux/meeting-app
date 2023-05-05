<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository
    extends ServiceEntityRepository
    implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Join on roles by default.
     */
    public function find (mixed $id, mixed $lockMode = null, mixed $lockVersion = null) : ?User
    {
        if ( ! $lockVersion )
            $dql = <<<DQL
                SELECT user, roles
                FROM App\Entity\User user
                    LEFT JOIN user.roles roles
                WHERE user.id = :id
                DQL;
        else
            $dql =<<<DQL
                SELECT user, roles
                FROM App\Entity\User user
                    LEFT JOIN user.roles roles
                WHERE user.id = :id
                    AND user.lockVersion = :lockVersion
                DQL;

        $query = $this
            ->getEntityManager()
            ->createQuery($dql)
            ->setParameter('id', $id);

        if ( $lockVersion )
            $query->setParameter('lockVersion', $lockVersion);
        if ( $lockMode )
            $query->setLockMode($lockMode);

        return $query->getOneOrNullResult();
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

    public function loadUserByIdentifier (string $identifier) : ?UserInterface
    {
        return $this
            ->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT user, roles
                FROM App\Entity\User user
                    LEFT JOIN user.roles roles
                WHERE user.email = :identifier
                    OR user.nickname = :identifier
                DQL
            )
            ->setParameter('identifier', $identifier)
            ->getOneOrNullResult();
    }

    public function findAllNicknameAndEmail()
    {
        $qb = $this -> createQueryBuilder('user')
            ->select('user.nickname, user.email');

        $query = $qb->getQuery();
        return $query->getResult();
    }


    public function findByKey($research)
    {
        $qb = $this -> createQueryBuilder('user')
            ->where('user.nickname LIKE :research')
            ->setParameter('research', "%{$research}%");
        $query = $qb->getQuery();
        return $query->getResult();
    }

}
