<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Website;

use App\Command\AbstractCommand;
use App\Repository\ServerRepository;
use App\Repository\WebsiteRepository;
use App\Util\Website\SearchBuilder;
use Doctrine\ORM\EntityManagerInterface;

class SearchBuildCommand extends AbstractCommand
{
    protected static $defaultName = 'app:website:search:build';

    /** @var SearchBuilder */
    private $searchBuilder;

    public function __construct(EntityManagerInterface $entityManager, ServerRepository $serverRepository, WebsiteRepository $websiteRepository, SearchBuilder $searchRebuilder)
    {
        parent::__construct($entityManager, $serverRepository, $websiteRepository);
        $this->searchBuilder = $searchRebuilder;
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Build search data in websites');
    }

    protected function runCommand(): void
    {
        $websites = $this->getWebsites();

        foreach ($websites as $website) {
            $this->info('Domain {domain}', ['domain' => $website->getDomain()]);
            $this->searchBuilder->build($website);
        }
    }
}
