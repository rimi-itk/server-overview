<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace AppBundle\Command;

use AppBundle\Entity\Website;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var EntityRepository
     */
    protected $repo;

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
        $this->repo = $this->em->getRepository('AppBundle:Website');

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

    protected function getWebsites(array $query = [])
    {
        return $this->filterWebsites($this->repo->findBy($query));
    }

    protected function getWebsitesByTypes(array $types)
    {
        return $this->filterWebsites($this->repo->findByTypes($types));
    }

    protected function filterWebsites(array $websites)
    {
        $servers = $this->input->getOption('server');
        if (count($servers) > 0) {
            $websites = array_filter($websites, function (Website $website) use ($servers) {
                return in_array($website->getServer(), $servers, true);
            });
        }

        $domains = $this->input->getOption('domain');
        if (count($domains) > 0) {
            $websites = array_filter($websites, function (Website $website) use ($domains) {
                return in_array($website->getDomain(), $domains, true);
            });
        }

        return $websites;
    }

    protected function filterServerNames(array $serverNames)
    {
        $servers = $this->input->getOption('server');
        if (count($servers) > 0) {
            $serverNames = array_filter($serverNames, function ($serverName) use ($servers) {
                return in_array($serverName, $servers, true);
            });
        }

        return $serverNames;
    }

    protected function getWebsite(array $query = [])
    {
        $result = $this->repo->findBy($query);

        return (count($result) > 0) ? $result[0] : null;
    }

    protected function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
    }

    protected function writeln()
    {
        $args = func_get_args();
        call_user_func_array([$this->output, 'writeln'], $args);
    }
}
