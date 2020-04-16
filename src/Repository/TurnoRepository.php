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

    public function findByRoleAdmin($rango, $atendido)
    {
        if ( $atendido == 'TODOS') {
            $result = $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Turno t WHERE t.fechaHora BETWEEN :desde AND :hasta ORDER BY t.oficina, t.fechaHora')
            ->setParameter('desde', $rango['desde'] )
            ->setParameter('hasta', $rango['hasta'] )
            ->getResult();    
        } else {
            $result = $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Turno t WHERE t.fechaHora BETWEEN :desde AND :hasta and t.persona IS NOT NULL and t.atendido = :atendido ORDER BY t.oficina, t.fechaHora')
            ->setParameter('desde', $rango['desde'] )
            ->setParameter('hasta', $rango['hasta'] )
            ->setParameter('atendido', $atendido )
            ->getResult();    
        }
        return $result;
    }

    public function findWithRoleUser($rango, $atendido, $oficina)
    {
        if ( $atendido == 'TODOS') {
            $result = $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Turno t WHERE t.oficina = :oficina  AND t.fechaHora BETWEEN :desde AND :hasta ORDER BY t.oficina, t.fechaHora')
            ->setParameter('desde', $rango['desde'] )
            ->setParameter('hasta', $rango['hasta'] )
            ->setParameter(':oficina', $oficina)
            ->getResult();    
        } else {
            $result = $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Turno t WHERE t.oficina = :oficina AND t.fechaHora BETWEEN :desde AND :hasta and t.persona IS NOT NULL and t.atendido = :atendido ORDER BY t.oficina, t.fechaHora')
            ->setParameter('desde', $rango['desde'] )
            ->setParameter('hasta', $rango['hasta'] )
            ->setParameter(':oficina', $oficina)
            ->setParameter('atendido', $atendido )
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
            '
        )
        ->setParameter(':val', $value)
        ->getResult();
        
        return $result;
    }

    public function findPrimerDiaDisponibleByOficina($oficina_id)
        {
            $sql = "SELECT to_char(date(min(fecha_hora)), 'dd/mm/yyyy') as UltimoDiaDisponible FROM turno WHERE persona_id is null AND oficina_id = :oficina_id AND fecha_hora > now()";
            
            $em = $this->getEntityManager();
            $statement = $em->getConnection()->prepare($sql);
            $statement->bindValue('oficina_id', $oficina_id);
            $statement->execute();
            $result = $statement->fetchAll();
    
            $ultimoDiaDisponible = $result[0]['ultimodiadisponible'];
    
            return $ultimoDiaDisponible;
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
        foreach($result as $item) {
            $diasDisponibles[] = $item['diadisponible'];
        }

        return $diasDisponibles;
    }    

    public function findDiasOcupadosByOficina($oficina_id)
    {

        // Obtengo todos los días futuros de atención programada para una oficina en particular
        $sql = "SELECT DISTINCT to_char(date(fecha_hora), 'dd/mm/yyyy') as DiasPosibles FROM turno WHERE oficina_id = :oficina_id AND fecha_hora >= now() ORDER BY 1";

        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('oficina_id', $oficina_id);
        $statement->execute();
        $result = $statement->fetchAll();

        // Convierto arreglo multi asociativo a un asociativo simple (Doctrine retorna un array por cada registro)
        $diasPosibles = array();
        foreach($result as $item) {
            $diasPosibles[] = $item['diasposibles'];
        }

        // Obtengo días (futuros) con turnos disponibles para una oficina en particular
        $diasDisponibles = $this->findDiasDisponiblesByOficina($oficina_id);

        // Obtengo la diferencia
        $diferencia = array_diff($diasPosibles, $diasDisponibles);

        // Proceso la diferencia para obtener en un arreglo asociativo simple 
        // los días completamente ocupados para una oficina en particular
        $diasOcupados = array();
        foreach($diferencia as $item) {
            $diasOcupados[] = $item;
        }

        return $diasOcupados;
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
        foreach($result as $item) {
            $horariosDisponibles[] = $item['hora'];
        }

        return $horariosDisponibles;
    }    


    public function findTurno($oficina_id, $fecha_hora)
    {
        $result = $this->getEntityManager()
        ->createQuery('
            SELECT t
            FROM App\Entity\Turno t
            WHERE t.oficina = :oficina_id and t.fechaHora = :fecha_hora
            '
        )
        ->setParameter(':oficina_id', $oficina_id)
        ->setParameter(':fecha_hora', $fecha_hora)
        ->getSingleResult();
        
        return $result;
    }
}
