<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository;

use App\Entity\Website;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class WebsiteRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Website::class);
    }

    public function findByTypes(array $types)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.type IN (:types)')
            ->setParameter('types', $types)
            ->orderBy('s.name', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }
}
