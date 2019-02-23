<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Website;

use App\Command\AbstractCommand;
use App\Repository\ServerRepository;
use App\Repository\WebsiteRepository;
use App\Util\Website\SearchBuilder;
use Doctrine\ORM\EntityManagerInterface;

class SearchRebuildCommand extends AbstractCommand
{
    protected static $defaultName = 'app:website:search:rebuild';

    /** @var SearchBuilder */
    private $searchRebuilder;

    public function __construct(EntityManagerInterface $entityManager, ServerRepository $serverRepository, WebsiteRepository $websiteRepository, SearchBuilder $searchRebuilder)
    {
        parent::__construct($entityManager, $serverRepository, $websiteRepository);
        $this->searchRebuilder = $searchRebuilder;
    }

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Rebuild search data in websites');
    }

    protected function runCommand()
    {
        $websites = $this->getWebsites();

        foreach ($websites as $website) {
            $this->write($website->getDomain());
            $this->searchRebuilder->build($website);
            $this->writeln(' done.');
        }
    }
}
