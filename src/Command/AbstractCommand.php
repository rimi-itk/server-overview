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
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends Command
{
    use LoggerTrait;

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
    protected $entityManager;

    /**
     * @var ServerRepository
     */
    protected $serverRepository;

    /**
     * @var WebsiteRepository
     */
    protected $websiteRepository;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(EntityManagerInterface $entityManager, ServerRepository $serverRepository, WebsiteRepository $websiteRepository)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->serverRepository = $serverRepository;
        $this->websiteRepository = $websiteRepository;
    }

    public function log($level, $message, array $context = [])
    {
        if (!is_scalar($message)) {
            $message = json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $this->logger->log($level, $message, $context);
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
        $this->logger = new ConsoleLogger($this->output);

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
        $process = new Process([
            'ssh',
            '-o ConnectTimeout=10',
            '-o BatchMode=yes',
            '-o StrictHostKeyChecking=no',
            '-A',
            'deploy@'.$server->getName(),
            $command,
        ]);
        $process->run();

        return $process->getOutput();
    }

    /**
     * @param array $query
     *
     * @return Server[]
     */
    protected function getServers(array $query = [])
    {
        $query += [
            'enabled' => true,
        ];
        $orderBy = [
            'name' => 'ASC',
        ];

        return $this->filterServers($this->serverRepository->findBy($query, $orderBy));
    }

    /**
     * @param array $servers
     *
     * @return Server[]
     */
    protected function filterServers(array $servers)
    {
        $names = $this->input->getOption('server');
        if (\count($names) > 0) {
            $servers = array_filter($servers, function (Server $server) use ($names) {
                return \in_array($server->getName(), $names, true);
            });
        }

        return $servers;
    }

    /**
     * @param array $query
     *
     * @return Website[]
     */
    protected function getWebsites(array $query = [])
    {
        return $this->filterWebsites($this->websiteRepository->findBy($query));
    }

    /**
     * @param array $types
     *
     * @return Website[]
     */
    protected function getWebsitesByTypes(array $types)
    {
        return $this->filterWebsites($this->websiteRepository->findByTypes($types));
    }

    /**
     * @param Website[] $websites
     *
     * @return Website[]
     */
    protected function filterWebsites(array $websites)
    {
        $servers = $this->input->getOption('server');
        if (\count($servers) > 0) {
            $websites = array_filter($websites, function (Website $website) use ($servers) {
                return \in_array($website->getServer()->getName(), $servers, true);
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
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    protected function debug()
    {
        if ($this->output->isDebug()) {
            $args = \func_get_args();
            foreach ($args as &$arg) {
                if (!is_scalar($arg)) {
                    $arg = json_encode($arg, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }
            \call_user_func_array([$this->output, 'writeln'], $args);
        }
    }
}
