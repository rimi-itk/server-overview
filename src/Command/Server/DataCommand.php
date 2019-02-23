<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Server;

use App\Command\AbstractCommand;
use App\Entity\Server;

class DataCommand extends AbstractCommand
{
    protected static $defaultName = 'app:server:data';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Get data from all servers');
    }

    protected function runCommand()
    {
        $servers = $this->serverRepository->findEnabled();

        foreach ($servers as $server) {
            $this->output->writeln($server);

            $data = [];

            try {
                $data['php'] = $this->getPHPData($server);
            } catch (\Exception $e) {
                $this->showException($e, $server);
            }

            try {
                $data['apache'] = $this->getApacheData($server);
            } catch (\Exception $e) {
                $this->showException($e, $server);
            }

            try {
                $data['nginx'] = $this->getNginxData($server);
            } catch (\Exception $e) {
                $this->showException($e, $server);
            }

            try {
                $data['mysql'] = $this->getMysqlData($server);
            } catch (\Exception $e) {
                $this->showException($e, $server);
            }

            $server->setData($data);
            $this->persist($server);
        }
    }

    private function showException(\Exception $e, Server $server)
    {
        if ($this->input->getOption('verbose')) {
            $this->writeln([
                str_repeat('-', 80),
                $server,
                $e->getMessage(),
                str_repeat('-', 80),
            ]);
        }
    }

    private function getPHPData(Server $server)
    {
        $cmd = 'php -r "echo json_encode(array(\"path\" => defined(\"PHP_BINARY\") ? PHP_BINARY : null, \"full_version\" => phpversion(), \"extensions\" => get_loaded_extensions()));"';
        $output = $this->runOnServer($server, $cmd);
        $data = json_decode($output, true);

        if (isset($data['full_version']) && preg_match('/^(?P<version>[0-9]+(?:\.[0-9]+){2})/', $data['full_version'], $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }

    private function getApacheData(Server $server)
    {
        $cmd = 'apache2 -v';
        $output = $this->runOnServer($server, $cmd);
        $data = ['output' => $output];

        if (preg_match('@(?P<version>[0-9]+(?:\.[0-9]+){2})@', $output, $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }

    private function getNginxData(Server $server)
    {
        $cmd = 'nginx -v 2>&1';
        $output = $this->runOnServer($server, $cmd);
        $data = ['output' => $output];

        if (preg_match('@(?P<version>[0-9]+(?:\.[0-9]+){2})@', $output, $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }

    private function getMysqlData(Server $server)
    {
        $cmd = 'mysql -V';
        $output = $this->runOnServer($server, $cmd);
        $data = ['output' => $output];

        if (preg_match('@(?P<version>[0-9]+(?:\.[0-9]+){2})@', $output, $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }
}
