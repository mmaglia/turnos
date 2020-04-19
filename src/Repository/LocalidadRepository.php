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
}
