<?php

/*
 * This file is part of ITK Sites.
 *
 * (c) 2018 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace App\Command;

use App\Entity\Website;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UpdateCommand extends Command
{
    /** @var \App\Command\ParameterBagInterface */
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        parent::__construct();
        $this->params = $params;
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('app:website:update')
            ->setDescription('Update list of sites');
    }

    protected function runCommand()
    {
        $serverNames = $this->getServerNames();

        foreach ($serverNames as $serverName) {
            $existing = $this->repo->findBy(['server' => $serverName]);
            foreach ($existing as $existing) {
                $this->em->remove($existing);
            }
            $this->em->flush();

            $this->writeln($serverName);
            // $cmd = 'ssh -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@' . $serverName . ' "for f in \$(sudo /usr/sbin/apachectl  -t -D DUMP_VHOSTS | grep namevhost | sed \'s/^.*namevhost *\([^ ]*\) *(\([^:)]*\).*$/\2/\'); do echo --- \$f; grep \'^[[:space:]]*\(Server\(Name\|Alias\)\|DocumentRoot\)\' \$f; done"';
            $cmd = 'ssh -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@'.$serverName.' "for f in /etc/{apache,nginx}*/sites-enabled/*; do echo --- \$f; [ -e $f ] && grep --no-messages \'^[[:space:]]*\(server_name\|root\|Server\(Name\|Alias\)\|DocumentRoot\)\' \$f; done"';

            $lines = [];
            $code = 0;
            exec($cmd, $lines, $code);

            if (!empty($lines)) {
                $lines = array_map(function ($line) {
                    return trim($line);
                }, $lines);
                $indexes = [];
                foreach ($lines as $index => $line) {
                    if (preg_match('/^---/', $line)) {
                        $indexes[] = $index;
                    }
                }
                $indexes[] = count($lines);

                foreach ($indexes as $index => $value) {
                    if ($index > 0) {
                        $chunk = array_slice($lines, $indexes[$index - 1], $value - $indexes[$index - 1], true);
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
                            } elseif (preg_match('/^(?:root|DocumentRoot)\s+(.+)/', $line, $matches)) {
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
                                            ->setServer($serverName)
                                            ->setDocumentRoot($documentRoot);

                                        $this->writeln('  '.$website);

                                        $this->persist($website);
                                    }
                                    $domains = null;
                                }
                            }
                        }
                    }
                }
            }
            $this->writeln('');
        }
    }

    private function getServerNames()
    {
        $serverNames = array_filter(preg_split('/\s*,\s*/', $this->params->get('server_names'), -1, PREG_SPLIT_NO_EMPTY));

        return $this->filterServerNames($serverNames);
    }
}
