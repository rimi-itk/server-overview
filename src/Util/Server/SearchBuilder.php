<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Util\Server;

use App\Entity\Server;
use Doctrine\ORM\EntityManagerInterface;

class SearchBuilder
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function build(Server $server): void
    {
        $data = $this->getSearchData($server);
        $server->setSearch($data);
        $this->entityManager->persist($server);
        $this->entityManager->flush();
    }

    private function getSearchData(Server $server): string
    {
        $data[] = $server->getName();

        $serverData = $server->getData();
        $features = array_filter($serverData, static function ($feature) {
            return isset($feature['version']);
        });
        foreach ($features as $name => $feature) {
            $data[] = $name;
            $data[] = $name.':'.$feature['version'];
            if (isset($feature['extensions'])) {
                if (\is_array($feature['extensions'])) {
                    foreach ($feature['extensions'] as $extension) {
                        $data[] = $name.':ext:'.$extension;
                    }
                } else {
                    $data[] = $feature['extensions'];
                }
            }
        }

        return implode(' ', $data);
    }
}
