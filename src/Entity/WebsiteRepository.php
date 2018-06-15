<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Entity;

use Doctrine\ORM\EntityRepository;

class WebsiteRepository extends EntityRepository
{
    public function findByTypes(array $types)
    {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT w FROM AppBundle:Website w WHERE w.type IN (:types)'
            )
            ->setParameter('types', $types)
            ->getResult();
    }
}
