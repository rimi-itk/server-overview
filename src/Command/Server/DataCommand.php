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
use Exception;

class DataCommand extends AbstractCommand
{
    protected static $defaultName = 'app:server:data';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Get data from all servers');
    }

    protected function runCommand(): void
    {
        $servers = $this->getServers();

        foreach ($servers as $server) {
            $this->info('Server {server}', ['server' => $server->getName()]);

            $data = [];

            try {
                $data['php'] = $this->getPHPData($server);
            } catch (Exception $e) {
                $this->showException($e, $server);
            }

            try {
                $data['apache'] = $this->getApacheData($server);
            } catch (Exception $e) {
                $this->showException($e, $server);
            }

            try {
                $data['nginx'] = $this->getNginxData($server);
            } catch (Exception $e) {
                $this->showException($e, $server);
            }

            try {
                $data['mysql'] = $this->getMysqlData($server);
            } catch (Exception $e) {
                $this->showException($e, $server);
            }

            $this->debug($data);

            $server->setData($data);
            $this->persist($server);
        }
    }

    private function showException(Exception $e, Server $server): void
    {
        $this->error($server->getName().': '.$e->getMessage(), ['exception' => $e]);
    }

    private function getPHPData(Server $server)
    {
        $cmd = 'php -r "echo json_encode(array(\"path\" => defined(\"PHP_BINARY\") ? PHP_BINARY : null, \"full_version\" => phpversion(), \"extensions\" => get_loaded_extensions()));"';
        $output = $this->runOnServer($server, $cmd);

        $data = $this->parseJson($output);
        if (isset($data['full_version']) && preg_match('/^(?P<version>\d+(?:\.\d+){2})/', $data['full_version'], $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }

    private function getApacheData(Server $server): array
    {
        $cmd = 'apache2 -v';
        $output = $this->runOnServer($server, $cmd);
        $data = ['output' => $output];

        if (preg_match('@(?P<version>\d+(?:\.\d+){2})@', $output, $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }

    private function getNginxData(Server $server): array
    {
        $cmd = 'nginx';
        $output = $this->runOnServer($server, $cmd);
        $data = ['output' => $output];

        if (preg_match('@(?P<version>\d+(?:\.\d+){2})@', $output, $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }

    private function getMysqlData(Server $server): array
    {
        $cmd = 'mysql -V';
        $output = $this->runOnServer($server, $cmd);
        $data = ['output' => $output];

        if (preg_match('@(?P<version>\d+(?:\.\d+){2}(?:-[a-z]+)?)@i', $output, $matches)) {
            $data['version'] = $matches['version'];
        }

        return $data;
    }
}
