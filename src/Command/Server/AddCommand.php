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
use App\Entity\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddCommand extends AbstractCommand
{
    protected static $defaultName = 'app:server:add';

    protected function configure()
    {
        $this->setDescription('Add a server')
            ->addArgument('name', InputArgument::REQUIRED, 'The server name')
            ->addOption('enabled', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $enabled = $input->getOption('enabled');
        if (null === $enabled) {
            throw new RuntimeException('Please specify --enabled.');
        }
        $enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);

        $isNew = false;
        $server = $this->serverRepository->findOneBy(['name' => $name]);
        if (null === $server) {
            $server = (new Server())
                ->setName($name);
        }
        $server->setEnabled($enabled);
        $this->entityManager->persist($server);
        $this->entityManager->flush();

        $output->writeln($isNew ? sprintf('Server %s created', $name) : sprintf('Server %s updated', $name));

        return Command::SUCCESS;
    }

    protected function runCommand(): void
    {
        throw new RuntimeException(__METHOD__.' should not be called.');
    }
}
