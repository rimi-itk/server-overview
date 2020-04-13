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
use App\Entity\Website;

class GetCommand extends AbstractCommand
{
    protected static $defaultName = 'app:website:get';

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Get websites from servers');
    }

    protected function runCommand(): void
    {
        $servers = $this->getServers();

        foreach ($servers as $server) {
            $this->info('Server {server}', ['server' => $server->getName()]);

            $websites = $this->websiteRepository->findBy(['server' => $server]);
            foreach ($websites as $website) {
                $website->setEnabled(false);
                $this->persist($website, false);
            }
            $this->flush();

            $cmd = 'ssh -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@'.$server->getName().' "for f in /etc/{apache,nginx}*/sites-enabled/*; do echo --- \$f; [ -e $f ] && grep --no-messages \'^[[:space:]]*\(server_name\|root\|proxy_pass\|Server\(Name\|Alias\)\|DocumentRoot\)\' \$f; done"';

            $lines = [];
            $code = 0;
            exec($cmd, $lines, $code);

            if (!empty($lines)) {
                $lines = array_map('trim', $lines);
                $indexes = [];
                foreach ($lines as $index => $line) {
                    if (0 === strpos($line, '---')) {
                        $indexes[] = $index;
                    }
                }
                $indexes[] = \count($lines);

                foreach ($indexes as $index => $value) {
                    if ($index > 0) {
                        $chunk = \array_slice($lines, $indexes[$index - 1], $value - $indexes[$index - 1], true);
                        $configFilename = null;
                        $domains = null;
                        $documentRoot = null;
                        foreach ($chunk as $line) {
                            if (preg_match('/^---\s(.+)/', $line, $matches)) {
                                $configFilename = $matches[1];
                            } elseif (preg_match('/^(?:server_name|Server(?:Name|Alias))\s+(.+)/', $line, $matches)) {
                                if (!$domains) {
                                    $domains = [];
                                }
                                foreach (preg_split('/\s+/', trim($matches[1], ';'), -1, PREG_SPLIT_NO_EMPTY) as $domain) {
                                    $domains[] = $domain;
                                }
                            } elseif (preg_match('/^(?:root|DocumentRoot|proxy_pass)\s+(.+)/', $line, $matches)) {
                                if ($domains) {
                                    $domains = array_unique(array_map(static function ($domain) {
                                        return preg_replace('/^www\./', '', $domain);
                                    }, $domains));
                                    $documentRoot = trim($matches[1], ';');

                                    foreach ($domains as $domain) {
                                        $website = $this->getWebsite(['domain' => $domain]);
                                        if (!$website) {
                                            $website = new Website();
                                        }
                                        $website
                                            ->setDomain($domain)
                                            ->setServer($server)
                                            ->setEnabled(true)
                                            ->setDocumentRoot($documentRoot);

                                        $this->info('  '.$website->getDomain());

                                        $this->persist($website);
                                    }
                                    $domains = null;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
