<?php

namespace App\Repository;

use App\Entity\Organismo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Organismo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Organismo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Organismo[]    findAll()
 * @method Organismo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrganismoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organismo::class);
    }

    /**
     * Se modifica el findAll para darle un orden alfabético
     */
    public function findAllOrdenado()
    {
        return $this->createQueryBuilder('o')
            ->innerJoin('o.localidad', 'l')
            ->orderBy('l.localidad, o.organismo', 'ASC') 
            ->getQuery()
            ->getResult();
    }

    public function findAllOrganismos()
    {
        $sql = "SELECT o.id, concat(o.organismo, ' (', l.localidad, ')') as Organismo FROM organismo o INNER JOIN localidad l ON l.id = o.localidad_id ORDER BY l.localidad, 2";
                    
        $em = $this->getEntityManager();
        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();

        return $result;
    }    

}
