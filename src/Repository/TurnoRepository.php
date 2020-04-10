<?php

namespace App\Repository;

use App\Entity\Turno;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Turno|null find($id, $lockMode = null, $lockVersion = null)
 * @method Turno|null findOneBy(array $criteria, array $orderBy = null)
 * @method Turno[]    findAll()
 * @method Turno[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TurnoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Turno::class);
    }

    public function findUltimoTurnoByOficina($value)
    {
        $result = $this->getEntityManager()
        ->createQuery('
            SELECT t
            FROM App\Entity\Turno t
            WHERE t.oficina = :val and t.fechaHora in (SELECT max(t2.fechaHora) FROM App\Entity\Turno t2 WHERE t2.oficina = :val)
            '
        )
        ->setParameter(':val', $value)
        ->getResult();
        
        return $result;

/*        return $this->createQueryBuilder('t')
            ->select('MAX(t.fechaHora) AS ultimoTurno')
            ->andWhere('t.oficina = :val')
            ->setParameter('val', $value)
            ->setMaxResults(1)
            ->orderBy('ultimoTurno', 'DESC')
            ->getQuery()
        ;
*/        
    }


    // /**
    //  * @return Turno[] Returns an array of Turno objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Turno
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
