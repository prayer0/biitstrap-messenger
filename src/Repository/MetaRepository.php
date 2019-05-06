<?php

namespace App\Repository;

use App\Entity\Meta;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class MetaRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Meta::class);
    }

    /*
    public function findBySomething($value)
    {
        return $this->createQueryBuilder('u')
            ->where('u.something = :value')->setParameter('value', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    public function getKey($key)
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery("
            SELECT
                m.metavalue
            FROM App\Entity\Meta m
            WHERE
                m.metakey = :key
        ");

        return $query
                ->setParameter('key', $key)
                ->setMaxResults(1)
                ->getSingleScalarResult();
    }
}
