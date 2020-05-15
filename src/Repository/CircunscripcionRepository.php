<?php

namespace App\Repository;

use App\Entity\Circunscripcion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Circunscripcion|null find($id, $lockMode = null, $lockVersion = null)
 * @method Circunscripcion|null findOneBy(array $criteria, array $orderBy = null)
 * @method Circunscripcion[]    findAll()
 * @method Circunscripcion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CircunscripcionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Circunscripcion::class);
    }
 
}
