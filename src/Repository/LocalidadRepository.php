<?php

namespace App\Repository;

use App\Entity\Localidad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Localidad|null find($id, $lockMode = null, $lockVersion = null)
 * @method Localidad|null findOneBy(array $criteria, array $orderBy = null)
 * @method Localidad[]    findAll()
 * @method Localidad[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LocalidadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Localidad::class);
    }

    /**
     * Se modifica el findAll para darle un orden alfabético
     */
    public function findAllOrdenado()
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.localidad', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findLocalidadesByCircunscripcion($circunscripcion_id)
    {
        return $this->createQueryBuilder('o')
            ->select('o.id, o.localidad as localidad')
            ->andWhere('o.circunscripcion = :val')
            ->setParameter('val', $circunscripcion_id)
            ->orderBy('localidad')
            ->getQuery()
            ->getArrayResult();
    }
}
