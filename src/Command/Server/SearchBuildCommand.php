<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2020 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Server;

use App\Command\AbstractCommand;
use App\Repository\ServerRepository;
use App\Repository\WebsiteRepository;
use App\Util\Server\SearchBuilder;
use Doctrine\ORM\EntityManagerInterface;

class SearchBuildCommand extends AbstractCommand
{
    protected static $defaultName = 'app:server:search:build';

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
        $this->setDescription('Build search data in servers');
    }

    protected function runCommand(): void
    {
        $servers = $this->getServers();

        foreach ($servers as $server) {
            $this->info('Server {name}', ['name' => $server->getName()]);
            $this->searchBuilder->build($server);
        }
    }
}
