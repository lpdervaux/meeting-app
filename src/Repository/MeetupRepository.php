<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Meetup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\AST\LikeExpression;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Meetup>
 *
 * @method Meetup|null find($id, $lockMode = null, $lockVersion = null)
 * @method Meetup|null findOneBy(array $criteria, array $orderBy = null)
 * @method Meetup[]    findAll()
 * @method Meetup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MeetupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meetup::class);
    }

    public function save(Meetup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Meetup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findWithFilters($filters, $user)
    {
        $qb = $this->createQueryBuilder('meetup')
            ->innerJoin('meetup.coordinator', 'coordinator')
            ->addSelect('coordinator')
            ->leftJoin('meetup.attendees','attendee')
            ->addSelect('attendee')
            ->innerJoin('meetup.campus', 'campus')
            ->addSelect('campus');

        if($filters['campus'])
        {
           $qb->where('campus = :campus ')
               ->setParameter('campus', $filters['campus']);
        }

        if($filters['research'])
        {
            $qb->andWhere('meetup.name LIKE :research')
                ->setParameter('research',"%{$filters['research']}%");
        }

        if($filters['start'] && $filters['end'])
        {
            $qb->andWhere('meetup.start BETWEEN :start AND :end')
                ->setParameter('start', $filters['start'])
                ->setParameter('end', $filters['end']);
        }

        if($filters['coordinator'] && $filters['registered'] && $filters['no_registered'])
        {
            $qb->andWhere('(coordinator = :user OR attendee = :user OR attendee != :user OR attendee IS NULL)')
                ->setParameter('user', $user);
        }

        if($filters['coordinator'] && $filters['registered'] && !$filters['no_registered'])
        {
            $qb->andWhere('(coordinator = :user OR attendee = :user)')
                ->setParameter('user', $user);
        }

        if(!$filters['coordinator'] && $filters['registered'] && $filters['no_registered'])
        {
            $qb->andWhere('(attendee = :user OR attendee != :user OR attendee IS NULL)')
                ->setParameter('user', $user);
        }

        if($filters['coordinator'] && !$filters['registered'] && $filters['no_registered'])
        {
            $qb->andWhere('(coordinator = :user OR attendee != :user OR attendee IS NULL)')
                ->setParameter('user', $user);
        }

        if($filters['coordinator'] && !$filters['registered'] && !$filters['no_registered'])
        {
            $qb->andWhere('coordinator = :user')
                ->setParameter('user', $user);
        }

        if(!$filters['coordinator'] && $filters['registered'] && !$filters['no_registered'])
        {
            $qb->andWhere('attendee = :user')
                ->setParameter('user', $user);
        }

        if(!$filters['coordinator'] && !$filters['registered'] && $filters['no_registered'])
        {
            $qb->andWhere('(attendee != :user OR attendee IS NULL)')
                ->setParameter('user', $user);
        }

        if($filters['past'])
        {
            $qb->andWhere('meetup.end < :past')
                ->setParameter('past', new \DateTimeImmutable());
        }

        $qb->orderBy('meetup.start', 'DESC');
        $query = $qb->getQuery();
        return $query->getResult();
    }

//    /**
//     * @return Meetup[] Returns an array of Meetup objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Meetup
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
