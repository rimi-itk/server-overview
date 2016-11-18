<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

class WebsiteRepository extends EntityRepository  {
    public function findByTypes(array $types) {
        return $this->getEntityManager()
            ->createQuery(
                'SELECT w FROM AppBundle:Website w WHERE w.type IN (:types)'
            )
            ->setParameter('types', $types)
            ->getResult();
    }
}
