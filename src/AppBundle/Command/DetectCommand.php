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
use Symfony\Component\Console\Input\InputOption;

class DetectCommand extends Command
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('itksites:detect')
            ->setDescription('Detect type and version of all sites')
            ->addOption('types', null, InputOption::VALUE_REQUIRED, 'Type of sites to process');
    }

    protected function runCommand()
    {
        $types = array_filter(preg_split('/\s*,\s*/', $this->input->getOption('types'), PREG_SPLIT_NO_EMPTY));
        $websites = $types ? $this->getWebsitesByTypes($types) : $this->getWebsites();

        $detectors = [
            'drupal (multisite)' => [
                'getCommand' => function (Website $website) {
                    $siteDirectory = 'sites/'.$website->getDomain();

                    return "[ -e $siteDirectory ] && cd $siteDirectory && hash drush 2>/dev/null && drush status --format=json";
                },
                'getVersion' => function (array $output) {
                    $data = json_decode(implode('', $output), true);

                    return isset($data['drupal-version']) ? $data['drupal-version'] : null;
                },
            ],
            'drupal' => [
                'command' => 'hash drush 2>/dev/null && drush status --format=json',
                'getVersion' => function (array $output) {
                    $data = json_decode(implode('', $output), true);

                    return isset($data['drupal-version']) ? $data['drupal-version'] : null;
                },
            ],
            'symfony' => [
                'command' => '[ -e ../app/console ] && ../app/console --version 2>/dev/null',
                'getVersion' => function (array $output) {
                    return preg_match('/version\s+(?<version>[^\s]+)/', $output[0], $matches) ? $matches['version'] : null;
                },
            ],
            'unknown' => [
                'command' => 'true',
                'getVersion' => function (array $output) {
                    return 0;
                },
            ],
        ];

        foreach ($websites as $website) {
            $this->output->writeln($website->getDomain());

            $cmdTemplate = 'ssh  -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@'.$website->getServer()
                                     .' "cd '.$website->getDocumentRoot().' && {{ command }}"';

            foreach ($detectors as $type => $detector) {
                $command = isset($detector['getCommand']) ? $detector['getCommand']($website) : $detector['command'];
                $cmd = str_replace('{{ command }}', $command, $cmdTemplate);

                $output = null;
                $code = 0;

                @exec($cmd, $output, $code);
                if (0 === $code) {
                    $version = $detector['getVersion']($output, $website);
                    if (null !== $version) {
                        $website
                            ->setType(isset($detector['type']) ? $detector['type'] : $type)
                            ->setVersion($version);
                        $this->persist($website);

                        $this->output->writeln(implode("\t", [$website->getDomain(), $website->getType(), $website->getVersion()]));

                        break;
                    }
                }
            }
        }
    }
}
