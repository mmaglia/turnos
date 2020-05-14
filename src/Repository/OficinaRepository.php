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
                SELECT o.id, o.oficina, l.localidad as localidad, o.horaInicioAtencion, o.horaFinAtencion, o.frecuenciaAtencion, o.telefono, o.habilitada,
                        (select max(t.fechaHora) from App\Entity\Turno t where t.oficina = o) as ultimoTurno 
                FROM App\Entity\Oficina o left join o.localidad l
                ORDER BY l.localidad, o.horaInicioAtencion, o.oficina'
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

    public function findOficinasHabilitadasByLocalidad($localidad_id)
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.oficina as oficina')
            ->andWhere('o.habilitada = true and o.localidad = :val')
            ->setParameter('val', $localidad_id)
            ->orderBy('oficina')
            ->getQuery()
            ->getArrayResult();
        ;
    }

    public function findAllOficinas()
    {
        $sql = "SELECT o.id, concat(o.oficina, ' (', l.localidad, ')') as Oficina FROM oficina o INNER JOIN localidad l ON l.id = o.localidad_id ORDER BY l.localidad, 2";
                    
        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }    

    public function findById($oficinaId): ?Oficina
    {
        
        return $this->createQueryBuilder('o')
            ->andWhere('o.id = :val')
            ->setParameter('val', $oficinaId)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
