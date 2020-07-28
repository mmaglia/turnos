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

    public function findAll()
    {
        $result = $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Turno t ORDER BY t.oficina, t.fechaHora')
            ->getResult();

        return $result;
    }

    /**
     * Retorna los turnos solicitados según parámetros
     * @param $rango
     * @param $estado
     * @param $circunscripcionUsuario Circunscripción del usuario
     */
    public function findByRoleAdmin($rango, $estado, $circunscripcionUsuario = null)
    {
        if ($estado == 9) { // Todos            
            if (!is_null($circunscripcionUsuario)) {
                $consultaSQL = 'SELECT t FROM App\Entity\Turno t JOIN t.oficina o JOIN o.localidad l WHERE t.fechaHora BETWEEN :desde AND :hasta AND l.circunscripcion = :circunscripcion ORDER BY t.oficina, t.fechaHora';
                $result = $this->getEntityManager()
                    ->createQuery($consultaSQL)
                    ->setParameter('desde', $rango['desde'])
                    ->setParameter('hasta', $rango['hasta'])
                    ->setParameter('hasta', $rango['hasta'])
                    ->setParameter('circunscripcion', $circunscripcionUsuario)
                    ->getResult();
            } else {
                $consultaSQL = 'SELECT t FROM App\Entity\Turno t WHERE t.fechaHora BETWEEN :desde AND :hasta ORDER BY t.oficina, t.fechaHora';
                $result = $this->getEntityManager()
                    ->createQuery($consultaSQL)
                    ->setParameter('desde', $rango['desde'])
                    ->setParameter('hasta', $rango['hasta'])
                    ->setParameter('hasta', $rango['hasta'])
                    ->getResult();
            }
        } else {
            if (!is_null($circunscripcionUsuario)) {
                $consultaSQL = 'SELECT t FROM App\Entity\Turno t JOIN t.oficina o JOIN o.localidad l WHERE t.fechaHora BETWEEN :desde AND :hasta AND l.circunscripcion = :circunscripcion AND t.persona IS NOT NULL and t.estado = :estado ORDER BY t.oficina, t.fechaHora';
                $result = $this->getEntityManager()
                    ->createQuery($consultaSQL)
                    ->setParameter('desde', $rango['desde'])
                    ->setParameter('hasta', $rango['hasta'])
                    ->setParameter('estado', $estado)
                    ->setParameter('circunscripcion', $circunscripcionUsuario)
                    ->getResult();
            } else {
                $consultaSQL = 'SELECT t FROM App\Entity\Turno t WHERE t.fechaHora BETWEEN :desde AND :hasta AND t.persona IS NOT NULL and t.estado = :estado ORDER BY t.oficina, t.fechaHora';
                $result = $this->getEntityManager()
                    ->createQuery($consultaSQL)
                    ->setParameter('desde', $rango['desde'])
                    ->setParameter('hasta', $rango['hasta'])
                    ->setParameter('estado', $estado)
                    ->getResult();
            }
        }
        return $result;
    }

    public function findWithRoleUser($rango, $estado, $oficina)
    {
        if ($estado == 9) {
            $result = $this->getEntityManager()
                ->createQuery('SELECT t FROM App\Entity\Turno t WHERE t.oficina = :oficina  AND t.fechaHora BETWEEN :desde AND :hasta ORDER BY t.oficina, t.fechaHora')
                ->setParameter('desde', $rango['desde'])
                ->setParameter('hasta', $rango['hasta'])
                ->setParameter(':oficina', $oficina)
                ->getResult();
        } else {
            $result = $this->getEntityManager()
                ->createQuery('SELECT t FROM App\Entity\Turno t WHERE t.oficina = :oficina AND t.fechaHora BETWEEN :desde AND :hasta and t.persona IS NOT NULL and t.estado = :estado ORDER BY t.oficina, t.fechaHora')
                ->setParameter('desde', $rango['desde'])
                ->setParameter('hasta', $rango['hasta'])
                ->setParameter(':oficina', $oficina)
                ->setParameter('estado', $estado)
                ->getResult();
        }
        return $result;
    }


    public function findUltimoTurnoByOficina($value)
    {
        $result = $this->getEntityManager()
            ->createQuery('
            SELECT t
            FROM App\Entity\Turno t
            WHERE t.oficina = :val and t.fechaHora in (SELECT max(t2.fechaHora) FROM App\Entity\Turno t2 WHERE t2.oficina = :val)
            ')
            ->setParameter(':val', $value)
            ->getResult();

        return $result;
    }

    public function findPrimerDiaDisponibleByOficina($oficina_id)
    {
        $sql = "SELECT to_char(date(min(fecha_hora)), 'dd/mm/yyyy') as PrimerDiaDisponible FROM turno WHERE persona_id is null AND oficina_id = :oficina_id AND fecha_hora > now()";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('oficina_id', $oficina_id);
        $statement->execute();
        $result = $statement->fetchAll();

        $primerDiaDisponible = $result[0]['primerdiadisponible'];

        return $primerDiaDisponible;
    }

    public function findUltimoDiaDisponibleByOficina($oficina_id)
    {
        $sql = "SELECT to_char(date(max(fecha_hora)), 'dd/mm/yyyy') as UltimoDiaDisponible FROM turno WHERE persona_id is null AND oficina_id = :oficina_id AND fecha_hora > now()";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('oficina_id', $oficina_id);
        $statement->execute();
        $result = $statement->fetchAll();

        $ultimoDiaDisponible = $result[0]['ultimodiadisponible'];

        return $ultimoDiaDisponible;
    }

    public function findDiasDisponiblesByOficina($oficina_id)
    {
        // Obtengo días (futuros) con turnos disponibles para una oficina en particular
        $sql = "SELECT DISTINCT to_char(date(fecha_hora), 'dd/mm/yyyy') as DiaDisponible FROM turno WHERE persona_id is null AND oficina_id = :oficina_id AND fecha_hora >= now() ORDER BY 1";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('oficina_id', $oficina_id);
        $statement->execute();
        $result = $statement->fetchAll();

        // Convierto arreglo multi asociativo a un asociativo simple (Doctrine retorna un array por cada registro)
        $diasDisponibles = array();
        foreach ($result as $item) {
            $diasDisponibles[] = $item['diadisponible'];
        }

        return $diasDisponibles;
    }

    public function findHorariosDisponiblesByOficinaByFecha($oficina_id, $fecha)
    {
        // Obtengo días (futuros) con turnos disponibles para una oficina en particular
        $sql = "SELECT to_char(fecha_hora, 'HH24:MI') as hora FROM turno WHERE persona_id is null AND oficina_id = :oficina_id AND to_char(date(fecha_hora), 'dd-mm-yyyy') = :fecha and fecha_hora > now() order by fecha_hora";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('oficina_id', $oficina_id);
        $statement->bindValue('fecha', $fecha);
        $statement->execute();
        $result = $statement->fetchAll();

        // Convierto arreglo multi asociativo a un asociativo simple (Doctrine retorna un array por cada registro)
        $horariosDisponibles = array();
        $hora = '';
        foreach ($result as $item) {
            if ($item['hora'] == $hora) {
                continue; // Salteo el horario porque ya lo incluyó
            } else {
                $hora = $item['hora'];
            }

            $horariosDisponibles[] = $item['hora'];
        }

        return $horariosDisponibles;
    }


    public function findExisteTurnoLibreByOficinaByFecha($oficina_id, $desde, $hasta)
    {
        $result = $this->getEntityManager()
            ->createQuery('SELECT t.id FROM App\Entity\Turno t WHERE t.oficina = :oficina AND t.fechaHora BETWEEN :desde AND :hasta and t.persona IS NULL ORDER BY t.oficina, t.fechaHora')
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->setParameter(':oficina', $oficina_id)
            ->getResult();

        return $result;
    }


    public function findTurno($oficina_id, $fecha_hora)
    {
        $result = $this->getEntityManager()
            ->createQuery('
            SELECT t
            FROM App\Entity\Turno t
            WHERE t.oficina = :oficina_id and t.fechaHora = :fecha_hora
            ')
            ->setParameter(':oficina_id', $oficina_id)
            ->setParameter(':fecha_hora', $fecha_hora)
            ->getResult();

        return $result;
    }


    public function findTurnoLibre($oficina_id, $fecha_hora)
    {
        $result = $this->getEntityManager()
            ->createQuery('
            SELECT t
            FROM App\Entity\Turno t
            WHERE t.oficina = :oficina_id and t.fechaHora = :fecha_hora and t.persona is null
            ')
            ->setParameter(':oficina_id', $oficina_id)
            ->setParameter(':fecha_hora', $fecha_hora)
            ->getResult();

        if ($result) {
            return $result[0]; // Retorna primer turno encontrado
        }

        return $result;
    }



    public function deleteTurnosByDiaByOficina($oficina_id, $desde, $hasta)
    {

        return $this->getEntityManager()
            ->createQuery("DELETE FROM App\Entity\Turno t WHERE t.oficina = :oficina_id and t.persona IS NULL and t.fechaHora BETWEEN :desde AND :hasta")
            ->setParameter('oficina_id', $oficina_id)
            ->setParameter('desde', $desde)
            ->setParameter('hasta', $hasta)
            ->getResult();
    }

    // Obtiene total de turnos futuros creados para una oficina en particular o todas o las de la circunscripcion del usuario
    public function findCantidadTurnosExistentes($oficina_id = '', $circunscripcion_id = null)
    {
        // Si vino con parámetro de oficina, armo filtro por oficina
        $filter = '';
        if ($oficina_id) {
            $filter = 'AND t.oficina_id = :oficina_id';
        }

        $sql = "SELECT count(*) as cantidad FROM turno t WHERE t.fecha_hora > now() $filter";

        if (!is_null($circunscripcion_id)) {
            $filter .= ' AND l.circunscripcion_id = :circunscripcion_id';
            $sql = "SELECT count(*) as cantidad FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE fecha_hora > now() $filter";
        }
        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);

        // Evalúa sin asignar la variable de filtro
        if ($oficina_id) {
            $statement->bindValue('oficina_id', $oficina_id);
        }

        if (!is_null($circunscripcion_id)) {
            $statement->bindValue('circunscripcion_id', $circunscripcion_id);
        }

        $statement->execute();
        $result = $statement->fetchColumn();

        return $result;
    }

    // Obtiene total de turnos futuros reservados para una oficina en particular o todas o las de la circunscripcion del usuario
    public function findCantidadTurnosAsignados($oficina_id = '', $circunscripcion_id = null)
    {
        // Si vino con parámetro de oficina, arma filtro por oficina
        $filter = '';
        if ($oficina_id) {
            $filter = 'AND t.oficina_id = :oficina_id';
        }
        $sql = "SELECT count(*) as cantidad FROM turno t WHERE t.persona_id is not null $filter AND t.fecha_hora > now()";

        if (!is_null($circunscripcion_id)) {
            $filter .= ' AND l.circunscripcion_id = :circunscripcion_id';
            $sql = "SELECT count(*) as cantidad FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE t.persona_id is not null $filter AND t.fecha_hora > now()";
        }

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);

        // Evalúa sin asignar la variable de filtro
        if ($oficina_id) {
            $statement->bindValue('oficina_id', $oficina_id);
        }

        if (!is_null($circunscripcion_id)) {
            $statement->bindValue('circunscripcion_id', $circunscripcion_id);
        }

        $statement->execute();
        $result = $statement->fetchColumn();

        return $result;
    }

    public function findEstadistica($desde, $hasta, $oficinaId)
    {
        if ($oficinaId != 0) { // Todas las Oficinas
            $filtroOficina = "oficina_id = :oficinaId AND";
        } else {
            $filtroOficina = "";
        }

        $sql = "SELECT '$desde' as Desde, '$hasta' as Hasta,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta) as Total,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL) as Otorgados,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL and estado = 1) as NoAtendidos,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL and estado = 2) as Atendidos,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL and estado = 3) as NoAsistidos,
                        (SELECT count(*) FROM turno_rechazado tr WHERE $filtroOficina fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NOT NULL)
                        ) as Rechazados_Ocupados,
                        (SELECT count(*) FROM turno_rechazado tr WHERE $filtroOficina fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NULL)
                        ) as Rechazados_Libres
            ";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('desde', $desde);
        $statement->bindValue('hasta', $hasta);
        if ($oficinaId != 0) {
            $statement->bindValue('oficinaId', $oficinaId);
        }
        $statement->execute();
        $result = $statement->fetchAll();

        return $result[0];
    }

    public function findEstadisticaOralidad($desde, $hasta, $oficinaId)
    {
        if ($oficinaId != 0) { // Todas las Oficinas
            $filtroOficina = "oficina_id = :oficinaId AND";
        } else {
            $filtroOficina = "";
        }

        $sql = "SELECT '$desde' as Desde, '$hasta' as Hasta,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta) as Total,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL) as Otorgados,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL and (notebook or zoom)) as Otorgados_Tecno,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL and estado = 1) as NoAtendidos,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL and estado = 2) as Atendidos,
                        (SELECT count(*) FROM turno WHERE $filtroOficina fecha_hora BETWEEN :desde AND :hasta and persona_id IS NOT NULL and estado = 3) as NoAsistidos,
                        (SELECT count(*) FROM turno_rechazado tr WHERE $filtroOficina fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NOT NULL)
                        ) as Rechazados_Ocupados,
                        (SELECT count(*) FROM turno_rechazado tr WHERE $filtroOficina fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NULL)
                        ) as Rechazados_Libres
            ";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('desde', $desde);
        $statement->bindValue('hasta', $hasta);
        if ($oficinaId != 0) {
            $statement->bindValue('oficinaId', $oficinaId);
        }
        $statement->execute();
        $result = $statement->fetchAll();

        return $result[0];
    }


    public function findEstadisticaByCircunscripcion($desde, $hasta, $circunscripcion_id)
    {        
        //t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id
        $sql = "SELECT '$desde' as Desde, '$hasta' as Hasta,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta) as Total,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL) as Otorgados,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL and t.estado = 1) as NoAtendidos,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL and t.estado = 2) as Atendidos,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL and t.estado = 3) as NoAsistidos,
                        (SELECT count(*) FROM turno_rechazado tr INNER JOIN oficina o ON tr.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NOT NULL)
                        ) as Rechazados_Ocupados,
                        (SELECT count(*) FROM turno_rechazado tr INNER JOIN oficina o ON tr.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NULL)
                        ) as Rechazados_Libres
            ";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('desde', $desde);
        $statement->bindValue('hasta', $hasta);
        $statement->bindValue('circunscripcion_id', $circunscripcion_id);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result[0];
    }


    public function findEstadisticaByCircunscripcionOralidad($desde, $hasta, $circunscripcion_id)
    {        
        //t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id
        $sql = "SELECT '$desde' as Desde, '$hasta' as Hasta,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta) as Total,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL) as Otorgados,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL and (notebook or zoom)) as Otorgados_Tecno,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL and t.estado = 1) as NoAtendidos,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL and t.estado = 2) as Atendidos,
                        (SELECT count(*) FROM turno t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND t.fecha_hora BETWEEN :desde AND :hasta and t.persona_id IS NOT NULL and t.estado = 3) as NoAsistidos,
                        (SELECT count(*) FROM turno_rechazado tr INNER JOIN oficina o ON tr.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NOT NULL)
                        ) as Rechazados_Ocupados,
                        (SELECT count(*) FROM turno_rechazado tr INNER JOIN oficina o ON tr.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id = :circunscripcion_id AND fecha_hora_turno BETWEEN :desde AND :hasta AND EXISTS (
                            SELECT 1 FROM turno t WHERE t.fecha_hora = tr.fecha_hora_turno and t.oficina_id = tr.oficina_id AND t.persona_id IS NULL)
                        ) as Rechazados_Libres
            ";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('desde', $desde);
        $statement->bindValue('hasta', $hasta);
        $statement->bindValue('circunscripcion_id', $circunscripcion_id);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result[0];
    }

    public function findById($turnoId): ?Turno
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.id = :turnoId')
            ->setParameter('turnoId', $turnoId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // Busco datos del último turno de una misma persona
    // Aplica al Sistema de Oralidad Civil donde el ORG_ID se guarda en el DNI de la Persona
    public function findUltimoTurnoPersona($org_id)
    {
        $sql = "SELECT p.apellido, p.nombre, p.email, p.telefono 
                FROM persona p 
                WHERE p.id IN ( SELECT max(t.persona_id) 
                                FROM turno t INNER JOIN persona p2 ON t.persona_id = p2.id 
                                WHERE p2.dni IN (select codigo FROM organismo WHERE id = $org_id))";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();

        if ($result) {
            return $result[0];
        }

        return $result;
    }


    public function findTurnosByFecha($oficina_id, $fecha_hora)
    {
        $result = $this->getEntityManager()
            ->createQuery('
            SELECT t
            FROM App\Entity\Turno t
            WHERE t.oficina = :oficina_id and t.fechaHora BETWEEN :desde AND :hasta ORDER BY t.oficina, t.fechaHora
            ')
            ->setParameter(':oficina_id', $oficina_id)
            ->setParameter(':desde',  $fecha_hora->format('Y-m-d  00:00:00'))
            ->setParameter(':hasta', $fecha_hora->format('Y-m-d  23:59:59'))
            ->getResult();

        return $result;
    }
}
