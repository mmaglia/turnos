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
        
        $sql = "SELECT to_char(fecha, 'DD/MM/YYYY') as fecha, sum(cantidad) as cantidad FROM turnos_diarios WHERE $filtroOficina fecha BETWEEN :desde AND :hasta GROUP BY fecha ORDER BY fecha";
        
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


}
