<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository;

use App\Entity\Website;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

class WebsiteRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Website::class);
    }

    public function findByTypes(array $types)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.type IN (:types)')
            ->setParameter('types', $types)
            ->getQuery()
            ->getResult();
    }

    public static function getValuesList($property)
    {
        return [];
    }
}
