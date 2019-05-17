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
use Symfony\Component\Console\Input\InputOption;

class DetectCommand extends AbstractCommand
{
    protected static $defaultName = 'app:website:detect';

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('Detect type and version of sites')
            ->addOption('type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Type of sites to process');
    }

    protected function runCommand()
    {
        $types = $this->input->getOption('type');
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
            'symfony 4' => [
                'command' => '[ -e ../bin/console ] && APP_ENV=prod ../bin/console --version 2>/dev/null',
                'getVersion' => function (array $output) {
                    return preg_match('/symfony\s+(?<version>[^\s]+)/i', $output[0], $matches) ? $matches['version'] : null;
                },
            ],
            'symfony 3' => [
                'command' => '[ -e ../bin/console ] && ../bin/console --env=prod --version 2>/dev/null',
                'getVersion' => function (array $output) {
                    return preg_match('/symfony\s+(?<version>[^\s]+)/i', $output[0], $matches) ? $matches['version'] : null;
                },
            ],
            'symfony 2' => [
                'command' => '[ -e ../app/console ] && ../app/console --env=prod --version 2>/dev/null',
                'getVersion' => function (array $output) {
                    return preg_match('/version\s+(?<version>[^\s]+)/i', $output[0], $matches) ? $matches['version'] : null;
                },
                'type' => 'symfony',
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

            if (filter_var($website->getDocumentRoot(), FILTER_VALIDATE_URL)) {
                $website->setType('proxy')->setVersion('👻');
                $this->persist($website);

                continue;
            }

            $cmdTemplate = 'ssh -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@'.$website->getServer()
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
