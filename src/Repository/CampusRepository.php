<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Campus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Campus>
 *
 * @method Campus|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campus|null findOneBy(array $criteria, array $orderBy = null)
 * @method Campus[]    findAll()
 * @method Campus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campus::class);
    }

    public function save(Campus $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Campus $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByName($name=null) : array
    {
        $campusAndNo = array();

        $qb = $this->createQueryBuilder('campus')
            ->select('campus');
        $query = $qb->getQuery();
        $campusList = $query->getResult();

        foreach($campusList as $key => $value)
        {
            if($value->getName() == $name)
            {
                $campusAndNo['campus'] = $value;
                $campusAndNo['no'] = $key;
            }
        }
        return $campusAndNo;

       // return $query->getResult()[0];
    }

    public function findNameByNo($no)
    {
        $name = null;
        $qb = $this->createQueryBuilder('campus')
            ->select('campus');
        $query = $qb->getQuery();
        $campusList = $query->getResult();

        foreach($campusList as $key => $value)
        {
            if($key == $no)
            {
                $name = $value->getName();
            }
        }

        return $name;

    }



//    /**
//     * @return Campus[] Returns an array of Campus objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Campus
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
