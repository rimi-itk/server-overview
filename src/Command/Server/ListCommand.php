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
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class ListCommand extends AbstractCommand
{
    protected static $defaultName = 'app:server:list';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('List servers');
    }

    protected function runCommand(): void
    {
        $servers = $this->getServers();

        $table = new Table($this->output);
        $table->setHeaders(['Name', 'Websites']);
        $table->setColumnStyle(1, (new TableStyle())->setPadType(STR_PAD_LEFT));
        foreach ($servers as $server) {
            $table->addRow([
                $server->getName(),
                $server->getWebsites()->count(),
            ]);
        }

        $table->render();
    }
}
