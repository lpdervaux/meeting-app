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
        define(
            'CAMPUS_CONDITION',
            'campus = :campus'
        );
        define(
            'RESEARCH_CONDITION',
            'meetup.name LIKE :research'
        );
        define(
            'START_END_CONDITION',
            'meetup.start BETWEEN :start AND :end'
        );
        define(
            'COORDINATOR_CONDITION',
            'coordinator = :user AND :date IS NOT NULL'
        );
        define(
            'REGISTERED_CONDITION',
            'attendee = :user AND :date IS NOT NULL'
        );
        define(
            'NO_REGISTERED_CONDITION',
            '((attendee != :user OR attendee IS NULL) AND meetup.start > :date)'
        );
        define(
            'PAST_CONDITION',
            'meetup.end < :date AND :user IS NOT NULL'
        );

        $qb = $this->createQueryBuilder('meetup')
            ->innerJoin('meetup.coordinator', 'coordinator')
            ->addSelect('coordinator')
            ->leftJoin('meetup.attendees', 'attendee')
            ->addSelect('attendee')
            ->innerJoin('meetup.campus', 'campus')
            ->addSelect('campus');

        if ($filters['campus']) {
            $qb->where(CAMPUS_CONDITION)
                ->setParameter('campus', $filters['campus']);
        }

        if ($filters['research']) {
            $qb->andWhere(RESEARCH_CONDITION)
                ->setParameter('research', "%{$filters['research']}%");
        }

        if (
            $filters['start'] &&
            $filters['end']
        ) {
            $qb->andWhere(START_END_CONDITION)
                ->setParameter('start', $filters['start'])
                ->setParameter('end', $filters['end']);
        }

        if (
            $filters['coordinator'] &&
            $filters['registered'] &&
            $filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . COORDINATOR_CONDITION . '   OR 
                    ' . REGISTERED_CONDITION . '    OR 
                    ' . NO_REGISTERED_CONDITION . ' OR 
                    ' . PAST_CONDITION . '
                )'
            );
        }

        if (
            $filters['coordinator'] &&
            $filters['registered'] &&
            $filters['no_registered'] &&
            !$filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . COORDINATOR_CONDITION . '   OR 
                    ' . REGISTERED_CONDITION . '    OR 
                    ' . NO_REGISTERED_CONDITION . '
                )'
            );
        }

        if (
            $filters['coordinator'] &&
            $filters['registered'] &&
            !$filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . COORDINATOR_CONDITION . '   OR 
                    ' . REGISTERED_CONDITION . '    OR 
                    ' . PAST_CONDITION . '
                )'
            );
        }

        if (
            $filters['coordinator'] &&
            !$filters['registered'] &&
            $filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . COORDINATOR_CONDITION . '   OR 
                    ' . NO_REGISTERED_CONDITION . ' OR 
                    ' . PAST_CONDITION . '
                )'
            );
        }

        if (
            !$filters['coordinator'] &&
            $filters['registered'] &&
            $filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . REGISTERED_CONDITION . '    OR 
                    ' . NO_REGISTERED_CONDITION . ' OR 
                    ' . PAST_CONDITION . '
                )'
            );
        }

        if (
            $filters['coordinator'] &&
            $filters['registered'] &&
            !$filters['no_registered'] &&
            !$filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . COORDINATOR_CONDITION . '   OR 
                    ' . REGISTERED_CONDITION . '
                )'
            );
        }

        if (
            $filters['coordinator'] &&
            !$filters['registered'] &&
            !$filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . COORDINATOR_CONDITION . '   OR 
                    ' . PAST_CONDITION . '
                )'
            );
        }

        if (
            !$filters['coordinator'] &&
            !$filters['registered'] &&
            $filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . NO_REGISTERED_CONDITION . ' OR 
                    ' . PAST_CONDITION . '
                )'
            );
        }

        if (
            !$filters['coordinator'] &&
            $filters['registered'] &&
            $filters['no_registered'] &&
            !$filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . REGISTERED_CONDITION . '    OR 
                    ' . NO_REGISTERED_CONDITION . '
                )'
            );
        }

        if (
            !$filters['coordinator'] &&
            $filters['registered'] &&
            !$filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(
                '( 
                    ' . REGISTERED_CONDITION . '    OR
                    ' . PAST_CONDITION . '
                )'
            );
        }

        if (
            $filters['coordinator'] &&
            !$filters['registered'] &&
            $filters['no_registered'] &&
            !$filters['past']
        ) {
            $qb->andWhere(
                '(
                    ' . COORDINATOR_CONDITION . '   OR
                    ' . NO_REGISTERED_CONDITION . '
                )'
            );
        }

        if (
            $filters['coordinator'] &&
            !$filters['registered'] &&
            !$filters['no_registered'] &&
            !$filters['past']
        ) {
            $qb->andWhere(COORDINATOR_CONDITION);
        }

        if (
            !$filters['coordinator'] &&
            $filters['registered'] &&
            !$filters['no_registered'] &&
            !$filters['past']
        ) {
            $qb->andWhere(REGISTERED_CONDITION);
        }

        if (
            !$filters['coordinator'] &&
            !$filters['registered'] &&
            $filters['no_registered'] &&
            !$filters['past']
        ) {
            $qb->andWhere(NO_REGISTERED_CONDITION);
        }

        if (
            !$filters['coordinator'] &&
            !$filters['registered'] &&
            !$filters['no_registered'] &&
            $filters['past']
        ) {
            $qb->andWhere(PAST_CONDITION);
        }

        if (
            $filters['coordinator'] ||
            $filters['registered'] ||
            $filters['no_registered'] ||
            $filters['past']
        ) {
            $qb->setParameter('user', $user)
                ->setParameter('date', new \DateTimeImmutable());
        }

        $qb->orderBy('meetup.start', 'DESC');
        $query = $qb->getQuery();
        return $query->getResult();

    }

    public function findDetails (int $id) : ?Meetup
    {
        return $this
            ->getEntityManager()
            ->createQuery(
                <<<DQL
                SELECT meetup,
                    location,
                    city,
                    coordinator,
                    campus,
                    attendees
                FROM App\Entity\Meetup meetup
                    LEFT JOIN meetup.location location
                        LEFT JOIN location.city city
                    LEFT JOIN meetup.coordinator coordinator
                    LEFT JOIN meetup.campus campus
                    LEFT JOIN meetup.attendees attendees
                WHERE meetup.id = :id
                DQL
            )
            ->setParameter('id', $id)
            ->getOneOrNullResult();

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
