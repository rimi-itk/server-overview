<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Util\Website;

use App\Entity\Website;
use Doctrine\ORM\EntityManagerInterface;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class SearchBuilder
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function build(Website $website): void
    {
        $data = $this->getSearchData($website);
        $website->setSearch($data);
        $this->entityManager->persist($website);
        $this->entityManager->flush();
    }

    private function getSearchData(Website $website): string
    {
        $data[] = $website->getDomain();
        $data[] = 'type:'.$website->getType();
        $data[] = 'type:'.$website->getType().':'.$website->getVersion();

        $modulesData = $this->getModulesData($website);
        // Flatten modules data
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($modulesData));
        foreach ($iterator as $item) {
            $data[] = $item;
        }

        return implode(' ', $data);
    }

    private function getModulesData(Website $website): array
    {
        $modulesData = [];

        $data = $website->getData();
        $type = $website->getType();

        if (Website::TYPE_DRUPAL_MULTISITE === $type) {
            $type = Website::TYPE_DRUPAL;
        }

        if (isset($data[$type])) {
            $items = $data[$type]['Enabled'] ?? $data[$type]['installed'] ?? [];
            $status = 'installed';

            foreach ($items as $item) {
                $name = $item['name'] ?? $item['display_name'] ?? null;
                if ((null !== $name) && preg_match('/\((?P<name>[^)]+)\)/', $name, $matches)) {
                    $moduleName = $matches['name'];
                    $modulesData[] = $moduleName.':'.$status;
                    if (isset($item['version'])) {
                        $modulesData[] = $moduleName.':'.$item['version'].':'.$status;
                    }
                }
            }
        }

        return $modulesData;
    }
}
