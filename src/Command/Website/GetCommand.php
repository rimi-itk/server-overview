<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command\Website;

use App\Command\AbstractCommand;
use App\Entity\Website;

class GetCommand extends AbstractCommand
{
    protected static $defaultName = 'app:website:get';

    protected function configure()
    {
        parent::configure();
        $this->setDescription('Get websites from servers');
    }

    protected function runCommand()
    {
        $servers = $this->getServers();

        $allDomains = array_map(static function (Website $website) {
            return $website->getDomain();
        }, $this->websiteRepository->findAll());
        $activeDomains = [];

        foreach ($servers as $server) {
            $this->notice($server->getName());

            $cmd = 'ssh -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@'.$server->getName().' "for f in /etc/{apache,nginx}*/sites-enabled/*; do echo --- \$f; [ -e $f ] && grep --no-messages \'^[[:space:]]*\(server_name\|root\|proxy_pass\|Server\(Name\|Alias\)\|DocumentRoot\)\' \$f; done"';

            $lines = [];
            $code = 0;
            exec($cmd, $lines, $code);

            if (!empty($lines)) {
                $lines = array_map('trim', $lines);
                $indexes = [];
                foreach ($lines as $index => $line) {
                    if (preg_match('/^---/', $line)) {
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
                                $domains = array_merge($domains, preg_split('/\s+/', trim($matches[1], ';'), -1, PREG_SPLIT_NO_EMPTY));
                            } elseif (preg_match('/^(?:root|DocumentRoot|proxy_pass)\s+(.+)/', $line, $matches)) {
                                if ($domains) {
                                    $domains = array_unique(array_map(function ($domain) {
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
                                            ->setActive(true)
                                            ->setDocumentRoot($documentRoot);

                                        $this->info('  '.$website->getDomain());

                                        $this->persist($website);
                                        $activeDomains[] = $domain;
                                    }
                                    $domains = null;
                                }
                            }
                        }
                    }
                }
            }
        }

        $inactiveDomains = array_diff($allDomains, $activeDomains);
        $inactiveWebsites = $this->websiteRepository->findBy(['domain' => $inactiveDomains]);
        foreach ($inactiveWebsites as $website) {
            $website->setActive(false);
            $this->persist($website, false);
            $this->info('  inactive: '.$website->getDomain());
        }
        $this->flush();
    }
}
