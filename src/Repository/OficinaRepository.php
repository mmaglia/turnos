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

            ->select('o.id, o.oficina as oficina, l.localidad, c.id as circunscripcion')
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

    public function findOficinasAgendasLlenas($umbralOcupacion = 80) {
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

    



}
