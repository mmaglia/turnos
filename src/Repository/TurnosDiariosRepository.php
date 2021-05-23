<?php

namespace App\Repository;

use App\Entity\TurnosDiarios;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TurnosDiarios|null find($id, $lockMode = null, $lockVersion = null)
 * @method TurnosDiarios|null findOneBy(array $criteria, array $orderBy = null)
 * @method TurnosDiarios[]    findAll()
 * @method TurnosDiarios[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TurnosDiariosRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TurnosDiarios::class);
    }

    public function findEstadistica($desde, $hasta, $oficinaId)
    {
        if ( $oficinaId != 0) { // Todas las Oficinas
            $filtroOficina = "oficina_id = :oficinaId AND";
        } else {
            $filtroOficina = "";
        }
        
        $sql = "SELECT to_char(fecha, 'DD/MM/YYYY') as fecha, sum(cantidad) as cantidad FROM turnos_diarios WHERE $filtroOficina fecha BETWEEN :desde AND :hasta GROUP BY fecha ORDER BY to_char(fecha, 'YYYY/MM/DD')";
        
        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('desde', $desde);
        $statement->bindValue('hasta', $hasta);
        if ( $oficinaId != 0) { 
            $statement->bindValue('oficinaId', $oficinaId);
        }
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }

    public function findEstadisticaByCircunscripcion($desde, $hasta, $circunscripcion_id)
    {
        
        $sql = "SELECT to_char(t.fecha, 'DD/MM/YYYY') as fecha, sum(t.cantidad) as cantidad FROM turnos_diarios t INNER JOIN oficina o ON t.oficina_id = o.id INNER JOIN localidad l on o.localidad_id = l.id WHERE l.circunscripcion_id in ($circunscripcion_id) AND t.fecha BETWEEN :desde AND :hasta GROUP BY t.fecha ORDER BY to_char(t.fecha, 'YYYY/MM/DD')";
        
        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('desde', $desde);
        $statement->bindValue('hasta', $hasta);

        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }

    public function findByOficinaByFecha($oficina_id, $fecha)
    {
        $result = $this->getEntityManager()
        ->createQuery("
            SELECT t
            FROM App\Entity\TurnosDiarios t
            WHERE t.oficina = :oficina_id and t.fecha = :fecha
            "
        )
        ->setParameter(':oficina_id', $oficina_id)
        ->setParameter(':fecha', $fecha)
        ->getOneOrNullResult();
        
        return $result;
    }



}
