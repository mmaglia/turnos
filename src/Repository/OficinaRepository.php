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
                SELECT o.id, o.oficina, l.localidad as localidad, o.horaInicioAtencion, o.horaFinAtencion, o.frecuenciaAtencion, o.telefono, o.habilitada, o.autoExtend, o.autoGestion, 
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

    public function findOficinasHabilitadasByLocalidadWithTelefono($localidad_id)
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.oficina as oficina, o.telefono')
            ->andWhere('o.habilitada = true and o.localidad = :val')
            ->setParameter('val', $localidad_id)
            ->orderBy('o.id')
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

    public function findUltimoTurnoById($oficinaId)
    {
        $result = $this->getEntityManager()
            ->createQuery("SELECT (select max(t.fechaHora) from App\Entity\Turno t where t.oficina = o) as ultimoTurno 
                            FROM App\Entity\Oficina o 
                            WHERE o.id = $oficinaId 
                            ")
            ->getOneOrNullResult();

        return $result['ultimoTurno'];
    }

    public function findByLocalidadesHabilitadasByCircunscripcion($circunscripcion_id)
    {
        return $this->createQueryBuilder('o')
            ->select('l.id, l.localidad as localidad')
            ->innerJoin('o.localidad', 'l', 'WITH', 'l.circunscripcion = :val')
            ->andWhere('o.habilitada = true')
            ->setParameter('val', $circunscripcion_id)
            ->distinct()
            ->orderBy('localidad')
            ->getQuery()
            ->getArrayResult();
        ;
    }


    public function findOficinasAutoExtend($oficinaIdDesde, $oficinaIdHasta)
    {
        return $this->createQueryBuilder('o')

            ->select('o.id, o.oficina as oficina, l.id as localidad_id, l.localidad, c.id as circunscripcion')
            ->innerJoin('o.localidad', 'l')
            ->innerJoin('l.circunscripcion', 'c')
            ->andWhere('o.habilitada = true and o.autoExtend = true and o.id >= :desde and o.id <= :hasta')
            ->setParameter('desde', $oficinaIdDesde)
            ->setParameter('hasta', $oficinaIdHasta)
            ->orderBy('oficina')
            ->getQuery()
            ->getArrayResult();
        ;
    }

    public function findOficinasAgendasLlenas($umbralOcupacion = 80)
    {
        $sql = "SELECT o.id, o.oficina, o.localidad_id, 
                        TRUNC(  (select count(*) from turno where fecha_hora > now() and persona_id is not null and turno.oficina_id = o.id)::decimal / 
                                (select count(*) from turno where fecha_hora > now() and turno.oficina_id = o.id)::decimal * 100,2) as Ocupacion
                FROM turno t inner join oficina o ON o.id = t.oficina_id
                WHERE t.fecha_hora > now() and o.auto_extend = true
                GROUP BY o.id, o.oficina, o.localidad_id
                HAVING ((select count(*) from turno where fecha_hora > now() and persona_id is not null and turno.oficina_id = o.id)::decimal / 
                        (select count(*) from turno where fecha_hora > now() and turno.oficina_id = o.id)::decimal * 100) >= :umbral
                ORDER BY   ((select count(*) from turno where fecha_hora > now() and persona_id is not null and turno.oficina_id = o.id)::decimal / 
                            (select count(*) from turno where fecha_hora > now() and turno.oficina_id = o.id)::decimal * 100) DESC
            ";
        
        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('umbral', $umbralOcupacion);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;

    }


    public function findMaximasOcupaciones($circunscripcionID, $orderBy)
    {

        $em = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT o.oficina, l.localidad, to_char(a.max, 'dd/mm/YYYY') as Maxima_Ocupacion, (a.max - now()::date) as dias,
                (select max(fecha_hora::date) from turno t where t.oficina_id = o.id) as ultimoTurno,
                ((select max(fecha_hora::date) from turno t where t.oficina_id = o.id) - a.max) as diasUltimoTurno
            FROM oficina o inner join localidad l on l.id = o.localidad_id left join 
                (select aux3.ofi,max(aux3.fec)
                 from (select aux2.t01 as ofi, aux2.t02 as fec, sum(t03) as tot
                        from (select t0.oficina_id as t01,t0.fecha_hora::date as t02,(case when t0.persona_id is null then 1 else 0 end) as t03
                            from turno t0, (select t1.oficina_id as t11, t1.fecha_hora::date as t12
                                                from turno t1
                                                group by 1,2) aux
                        where 	t0.oficina_id = aux.t11			       
                                and t0.fecha_hora::date = aux.t12) as aux2
                        group by 1,2) as aux3
                 where aux3.tot = 0
                 group by 1) a ON o.id = a.ofi
            WHERE  l.circunscripcion_id = $circunscripcionID and a.max is not null and a.max >= now()::date
            ORDER BY $orderBy
        ";
                    
        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();
        
        return $result;
    }    

}
