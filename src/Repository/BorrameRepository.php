<?php

namespace App\Repository;

use App\Entity\Borrame;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Borrame|null find($id, $lockMode = null, $lockVersion = null)
 * @method Borrame|null findOneBy(array $criteria, array $orderBy = null)
 * @method Borrame[]    findAll()
 * @method Borrame[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BorrameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Borrame::class);
    }

    // /**
    //  * @return Borrame[] Returns an array of Borrame objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Borrame
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
