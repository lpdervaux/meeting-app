<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Meetup;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
            //->innerJoin('meetup.attendees', 'attendee')
            //->addSelect('attendee')
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

        }

        if($filters['start'] && $filters['end'])
        {

        }

        if($filters['coordinator'])
        {
            $qb->andWhere('coordinator = :coordinator')
                ->setParameter('coordinator', $user);
        }

        if($filters['registered'])
        {
            $qb->andWhere('attendee = :user')
                ->setParameter('user', $user);
        }

        if($filters['no_registered'])
        {

        }

        if($filters['past'])
        {

        }

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
