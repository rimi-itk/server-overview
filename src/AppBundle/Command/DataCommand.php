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

class DataCommand extends Command
{
    protected function configure()
    {
        parent::configure();
        $this
            ->setName('itksites:data')
            ->setDescription('Get data from  all sites')
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

                    return "cd $siteDirectory && drush pm-list --format=csv";
                },
                'getData' => function (array $output, Website $website) {
                    $data = implode(PHP_EOL, $output);

                    return $data;
                },
            ],
            'drupal' => [
                'command' => 'drush pm-list --format=csv',
                'getData' => function (array $output) {
                    $data = implode(PHP_EOL, $output);

                    return $data;
                },
            ],
            'symfony' => [
                'command' => 'composer --working-dir=.. show --installed',
                'getData' => function (array $output) {
                    $data = implode("\n", $output);

                    return $data;
                },
            ],
        ];

        foreach ($websites as $website) {
            $this->output->writeln($website->getDomain());

            if (isset($detectors[$website->getType()])) {
                $this->output->writeln("\t".$website->getType());

                $detector = $detectors[$website->getType()];

                $cmdTemplate = 'ssh  -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@'.$website->getServer()
                                         .' "cd '.$website->getDocumentRoot().' && {{ command }}"';

                $command = isset($detector['getCommand']) ? $detector['getCommand']($website) : $detector['command'];
                $cmd = str_replace('{{ command }}', $command, $cmdTemplate);

                $output = null;
                $code = 0;

                @exec($cmd, $output, $code);
                if (0 === $code) {
                    $data = $detector['getData']($output, $website);
                    if (null !== $data) {
                        $website
                            ->setData($data);
                        $this->persist($website);
                    }
                }
            }
        }
    }
}
