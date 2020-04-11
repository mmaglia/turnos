<?php

namespace App\Repository;

use App\Entity\Oficina;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Oficina|null find($id, $lockMode = null, $lockVersion = null)
 * @method Oficina|null findOneBy(array $criteria, array $orderBy = null)
 * @method Oficina[]    findAll()
 * @method Oficina[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OficinaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oficina::class);
    }

    public function findAllWithUltimoTurno()
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT o.id, o.oficina, l.localidad as localidad, o.horaInicioAtencion, o.horaFinAtencion, o.frecuenciaAtencion, 
                        (select max(t.fechaHora) from App\Entity\Turno t where t.oficina = o) as ultimoTurno 
                FROM App\Entity\Oficina o join o.localidad l
                ORDER BY o.id'
            )
            ->getResult();
    }

    public function findOficinaByLocalidad($localidad_id)
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.oficina as oficina')
            ->andWhere('o.localidad = :val')
            ->setParameter('val', $localidad_id)
            ->orderBy('oficina')
            ->getQuery()
            ->getArrayResult();
        ;
    }

    // /**
    //  * @return Oficina[] Returns an array of Oficina objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('o.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Oficina
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
