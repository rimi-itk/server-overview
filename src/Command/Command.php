<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command;

use App\Entity\Server;
use App\Entity\Website;
use App\Repository\ServerRepository;
use App\Repository\WebsiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class Command extends ContainerAwareCommand
{
    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var ServerRepository
     */
    protected $serverRepository;

    /**
     * @var WebsiteRepository
     */
    protected $websiteRepository;

    public function __construct(ServerRepository $serverRepository, WebsiteRepository $websiteRepository)
    {
        parent::__construct();
        $this->serverRepository = $serverRepository;
        $this->websiteRepository = $websiteRepository;
    }

    protected function configure()
    {
        $this->addOption('list-types', null, InputOption::VALUE_NONE, 'List all website types');
        $this->addOption('server', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Server to process');
        $this->addOption('domain', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Domain to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->em = $this->getContainer()->get('doctrine')->getEntityManager('default');

        if ((bool) $this->input->getOption('list-types')) {
            $types = [];
            foreach ($this->getWebsites() as $website) {
                $type = $website->getType();
                if (!isset($types[$type])) {
                    $types[$type] = 0;
                }
                ++$types[$type];
            }
            ksort($types);
            foreach ($types as $type => $cardinality) {
                $this->output->writeln($type.': '.$cardinality);
            }
            exit;
        }

        $this->runCommand();
    }

    abstract protected function runCommand();

    protected function runOnServer(Server $server, $command)
    {
        $sshArguments = '-o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A';
        $host = 'deploy@'.$server->getName();

        $ssh = "ssh $sshArguments $host '$command'";
        $process = new Process($ssh);
        $process->mustRun();

        return $process->getOutput();
    }

    protected function getWebsites(array $query = [])
    {
        return $this->filterWebsites($this->websiteRepository->findBy($query));
    }

    protected function getWebsitesByTypes(array $types)
    {
        return $this->filterWebsites($this->websiteRepository->findByTypes($types));
    }

    /**
     * @param Website[] $websites
     *
     * @return array
     */
    protected function filterWebsites(array $websites)
    {
        $servers = $this->input->getOption('server');
        if (\count($servers) > 0) {
            $websites = array_filter($websites, function (Website $website) use ($servers) {
                return \in_array($website->getServer(), $servers, true);
            });
        }

        $domains = $this->input->getOption('domain');
        if (\count($domains) > 0) {
            $websites = array_filter($websites, function (Website $website) use ($domains) {
                return \in_array($website->getDomain(), $domains, true);
            });
        }

        return $websites;
    }

    protected function filterServerNames(array $serverNames)
    {
        $servers = $this->input->getOption('server');
        if (\count($servers) > 0) {
            $serverNames = array_filter($serverNames, function ($serverName) use ($servers) {
                return \in_array($serverName, $servers, true);
            });
        }

        return $serverNames;
    }

    protected function getWebsite(array $query = [])
    {
        $result = $this->websiteRepository->findBy($query);

        return (\count($result) > 0) ? $result[0] : null;
    }

    protected function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    protected function writeln()
    {
        $args = \func_get_args();
        \call_user_func_array([$this->output, 'writeln'], $args);
    }
}
