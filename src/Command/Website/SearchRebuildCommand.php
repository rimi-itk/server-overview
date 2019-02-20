<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Website;

use App\Command\Command;
use App\Repository\ServerRepository;
use App\Repository\WebsiteRepository;
use App\Util\Website\SearchBuilder;

class SearchRebuildCommand extends Command
{
    /** @var SearchBuilder */
    private $searchRebuilder;

    public function __construct(ServerRepository $serverRepository, WebsiteRepository $websiteRepository, SearchBuilder $searchRebuilder)
    {
        parent::__construct($serverRepository, $websiteRepository);
        $this->searchRebuilder = $searchRebuilder;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:website:search:rebuild')
            ->setDescription('Rebuild search data in websites');
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
