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
                FROM App\Entity\Oficina o LEFT JOIN o.localidad l
                ORDER BY l.localidad, o.horaInicioAtencion, o.oficina')
            ->getResult();
    }

    public function findOficinasByCircunscripcionWithUltimoTurno($circunscripcion_id)
    {
        return $this->getEntityManager()
            ->createQuery('
                SELECT o.id, o.oficina, l.localidad as localidad, o.horaInicioAtencion, o.horaFinAtencion, o.frecuenciaAtencion, o.telefono, o.habilitada, o.autoExtend, o.autoGestion, 
                        (select max(t.fechaHora) from App\Entity\Turno t where t.oficina = o) as ultimoTurno 
                FROM App\Entity\Oficina o LEFT JOIN o.localidad l
                WHERE l.circunscripcion = ' . $circunscripcion_id .
                ' ORDER BY l.localidad, o.horaInicioAtencion, o.oficina')
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
            ->getArrayResult();;
    }

    public function findOficinasHabilitadasByLocalidad($localidad_id)
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.oficina as oficina')
            ->andWhere('o.habilitada = true and o.localidad = :val')
            ->setParameter('val', $localidad_id)
            ->orderBy('oficina')
            ->getQuery()
            ->getArrayResult();;
    }

    public function findOficinasHabilitadasByLocalidadWithTelefono($localidad_id)
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.oficina as oficina, o.telefono')
            ->andWhere('o.habilitada = true and o.localidad = :val')
            ->setParameter('val', $localidad_id)
            ->orderBy('o.id')
            ->getQuery()
            ->getArrayResult();;
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

    public function findOficinasByCircunscripcion($circunscripcion_id)
    {
        $sql = "SELECT o.id, concat(o.oficina, ' (', l.localidad, ')') as Oficina FROM oficina o INNER JOIN localidad l ON l.id = o.localidad_id WHERE l.circunscripcion_id = $circunscripcion_id ORDER BY l.localidad, 2";

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
            ->getOneOrNullResult();
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
            ->getArrayResult();;
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
            ->getArrayResult();;
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

        // Evalúa si filtrar por Circunscripción
        $filtroCircunscripcion = '';
        if ($circunscripcionID) {
            $filtroCircunscripcion = ' AND l.circunscripcion_id = ' . $circunscripcionID;
        }

        $em = $this->getEntityManager()->getConnection();
        /*$sql = "SELECT o.oficina, l.localidad, to_char(a.max, 'dd/mm/YYYY') as Maxima_Ocupacion, (a.max - now()::date) as dias, (SELECT max(fecha_hora::date) FROM turno t WHERE t.oficina_id = o.id) as ultimoTurno, ((SELECT max(fecha_hora::date) FROM turno t WHERE t.oficina_id = o.id) - a.max) as diasUltimoTurno
                FROM oficina o 
                INNER JOIN localidad l ON l.id = o.localidad_id 
                LEFT JOIN (SELECT aux3.ofi,max(aux3.fec)
                    FROM (SELECT aux2.t01 as ofi, aux2.t02 as fec, sum(t03) as tot
                        FROM (SELECT t0.oficina_id as t01,t0.fecha_hora::date as t02,(case when t0.persona_id is null then 1 else 0 end) as t03
                            FROM turno t0, (SELECT t1.oficina_id as t11, t1.fecha_hora::date as t12
                                                FROM turno t1
                                                GROUP BY 1,2) aux
                            WHERE t0.oficina_id = aux.t11 AND t0.fecha_hora::date = aux.t12) as aux2
                            GROUP BY 1,2) as aux3
                    WHERE aux3.tot = 0
                    GROUP BY 1) a ON o.id = a.ofi
                WHERE  a.max is not null and a.max >= now()::date $filtroCircunscripcion
                ORDER BY $orderBy";*/
        $sql = "SELECT o.oficina, l.localidad,
                    (SELECT to_char(date(min(fecha_hora)), 'dd/mm/yyyy') FROM turno WHERE persona_id is null AND oficina_id = o.id AND fecha_hora > now()) as primer_turno_disponible, ((SELECT date(min(fecha_hora)) FROM turno WHERE persona_id is null AND oficina_id = o.id AND fecha_hora > now()) - now()::date) as diasprimerturno,
                    to_char(a.max, 'dd/mm/YYYY') AS Maxima_Ocupacion, (a.max - now()::date) AS dias, 
                    (SELECT MAX(fecha_hora::date) FROM turno t WHERE t.oficina_id = o.id) AS ultimoTurno, ((SELECT MAX(fecha_hora::date) FROM turno t WHERE t.oficina_id = o.id) - a.max) AS diasUltimoTurno
                FROM oficina o 
                INNER JOIN localidad l ON l.id = o.localidad_id 
                LEFT JOIN 
                    (SELECT aux3.ofi,MAX(aux3.fec)
                    FROM (SELECT aux2.t01 AS ofi, aux2.t02 AS fec, SUM(t03) AS tot
                            FROM (SELECT t0.oficina_id AS t01,t0.fecha_hora::date AS t02,(CASE WHEN t0.persona_id IS NULL THEN 1 ELSE 0 END) AS t03
                                FROM turno t0, (SELECT t1.oficina_id AS t11, t1.fecha_hora::date AS t12
                                                    FROM turno t1
                                                    GROUP BY 1,2) aux
                            WHERE t0.oficina_id = aux.t11 AND t0.fecha_hora::date = aux.t12) AS aux2
                            GROUP BY 1,2) AS aux3
                    WHERE aux3.tot = 0
                    GROUP BY 1) a ON o.id = a.ofi
                WHERE  a.max IS NOT NULL AND a.max >= now()::date $filtroCircunscripcion
                ORDER BY $orderBy";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }
}
