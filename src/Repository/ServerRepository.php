<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Repository;

use App\Entity\Server;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method null|Server find($id, $lockMode = null, $lockVersion = null)
 * @method null|Server findOneBy(array $criteria, array $orderBy = null)
 * @method Server[]    findAll()
 * @method Server[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Server::class);
    }

    /**
     * @return array
     */
    public function findEnabled()
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('s.name', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }
}
