<?php

namespace App\Repository;

use App\Entity\TurnoRechazado;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TurnoRechazado|null find($id, $lockMode = null, $lockVersion = null)
 * @method TurnoRechazado|null findOneBy(array $criteria, array $orderBy = null)
 * @method TurnoRechazado[]    findAll()
 * @method TurnoRechazado[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TurnoRechazadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TurnoRechazado::class);
    }

   /**
     * Retorna todos los registros ordenados por $column
     */
    public function findAllOrderedByColum($column, $sort = 'ASC', $oficina = null)
    {
        
        if ($oficina) {
            return $this->createQueryBuilder('t')
                ->andWhere('t.oficina = :oficina')
                ->setParameter('oficina', $oficina)
                ->addOrderBy('t.' . $column, $sort)
                ->getQuery()
                ->getResult();
        } else {
            return $this->createQueryBuilder('t')
                ->addOrderBy('t.' . $column, $sort)
                ->getQuery()
                ->getResult();
        }
    }     

    // /**
    //  * @return TurnoRechazado[] Returns an array of TurnoRechazado objects
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
    public function findOneBySomeField($value): ?TurnoRechazado
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
