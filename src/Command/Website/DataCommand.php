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
use App\Command\Website\Util\AbstractDataProvider;
use App\Entity\Website;
use Symfony\Component\Console\Input\InputOption;

class DataCommand extends AbstractCommand
{
    protected static $defaultName = 'app:website:data';

    protected function configure()
    {
        parent::configure();
        $this
            ->setDescription('Get data from sites')
            ->addOption('types', null, InputOption::VALUE_REQUIRED, 'Type of sites to process')
            ->addOption('key', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Data key to get');
    }

    protected function runCommand()
    {
        $types = array_filter(preg_split('/\s*,\s*/', $this->input->getOption('types'), PREG_SPLIT_NO_EMPTY));
        $keys = $this->input->getOption('key');
        $websites = $types ? $this->getWebsitesByTypes($types) : $this->getWebsites();

        $providers = $this->getProviders();

        foreach ($websites as $website) {
            $this->notice($website->getDomain());

            foreach ($providers as $provider) {
                $key = $provider->getKey();

                if (!empty($keys) && !\in_array($key, $keys, true)) {
                    continue;
                }

                if ($provider->canHandle($website)) {
                    $command = 'cd '.$website->getDocumentRoot().' && '.$provider->getCommand($website);
                    $output = $this->runOnServer($website->getServer(), $command);

                    $data = $provider->getData($output, $website);
                    if (null !== $data) {
                        if (!\is_array($data)) {
                            throw new \RuntimeException(\get_class($provider).' must return an array');
                        }
                        $this->debug([$key => $data]);
                        $website->addData([$key => $data]);
                        $this->persist($website);
                    }
                }
            }
        }
    }

    /**
     * @return AbstractDataProvider[]
     */
    private function getProviders()
    {
        return [
            // Drupal (multisite)
            new class() extends AbstractDataProvider {
                protected $key = 'drupal';

                public function canHandle(Website $website)
                {
                    return Website::TYPE_DRUPAL_MULTISITE === $website->getType()
                        || Website::TYPE_DRUPAL === $website->getType();
                }

                public function getCommand(Website $website)
                {
                    if (Website::TYPE_DRUPAL_MULTISITE === $website->getType()) {
                        $siteDirectory = 'sites/'.$website->getDomain();

                        return "cd $siteDirectory && drush pm-list --format=json";
                    }

                    return 'drush pm-list --format=json';
                }

                public function getData(string $output, Website $website)
                {
                    $data = json_decode($output) ?? [];

                    $buckets = [
                        'Enabled' => [],
                        'Disabled' => [],
                        'Not installed' => [],
                    ];

                    foreach ($data as $item) {
                        $buckets[$item->status][] = $item;
                    }

                    return $buckets;
                }
            },

            // Symfony
            new class() extends AbstractDataProvider {
                protected $key = 'symfony';

                protected $command = 'composer --working-dir=.. show --format=json';

                public function canHandle(Website $website)
                {
                    return Website::TYPE_SYMFONY === $website->getType();
                }

                public function getData(string $output, Website $website)
                {
                    $data = json_decode($output, true) ?? [];

                    return $data;
                }
            },

            // Git
            new class() extends AbstractDataProvider {
                protected $key = 'git';

                public function canHandle(Website $website)
                {
                    return null !== $website->getDocumentRoot();
                }

                public function getCommand(Website $website)
                {
                    return 'for d in $(find '.$website->getProjectDir().' -name .git | xargs dirname); do (cd $d && echo $d && git config --get remote.origin.url && git rev-parse --abbrev-ref HEAD && git rev-parse HEAD); done';
                }

                public function getData(string $output, Website $website)
                {
                    $lines = explode(PHP_EOL, $output);
                    $chunks = array_chunk($lines, 4);
                    $data = array_map(
                        function (array $chunk) {
                            return [
                                'path' => $chunk[0],
                                'remote' => preg_replace('/\.git$/', '', $chunk[1]),
                                'branch' => $chunk[2],
                                'commit' => $chunk[3],
                            ];
                        },
                        array_filter($chunks, function (array $chunk) {
                            return 4 === \count($chunk);
                        })
                    );

                    // Sort chunks by length of path
                    usort($data, function (array $a, array $b) {
                        return \strlen($a['path']) - \strlen($b['path']);
                    });

                    return $data;
                }
            },
        ];
    }
}
