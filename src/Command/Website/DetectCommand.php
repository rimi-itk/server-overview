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
use App\Command\Website\Util\AbstractDetector;
use App\Entity\Website;
use Symfony\Component\Console\Input\InputOption;

class DetectCommand extends AbstractCommand
{
    protected static $defaultName = 'app:website:detect';

    protected function configure(): void
    {
        parent::configure();
        $this
            ->setDescription('Detect type and version of sites')
            ->addOption('type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Type of sites to process');
    }

    protected function runCommand(): void
    {
        $types = $this->input->getOption('type');
        $websites = $types ? $this->getWebsitesByTypes($types) : $this->getWebsites();

        $detectors = $this->getDetectors();

        foreach ($websites as $website) {
            $this->info(sprintf('%-40s%-40s', $website->getServerName(), $website->getDomain()));

            foreach ($detectors as $detector) {
                $command = 'cd '.$website->getDocumentRoot().' && '.$detector->getCommand($website);
                $output = $this->runOnServer($website->getServer(), $command);

                $version = $detector->getVersion($output, $website);
                if (null !== $version) {
                    $website
                        ->setType($detector->getType())
                        ->setVersion($version);
                    $this->persist($website);

                    $this->info(sprintf(
                        '%-40s%-40s',
                        $website->getType(),
                        $website->getVersion()
                    ));

                    break;
                }
            }
        }
    }

    /**
     * @return AbstractDetector[]
     */
    private function getDetectors(): array
    {
        return [
            // Proxy
            new class(Website::TYPE_PROXY) extends AbstractDetector {
                protected $command = 'true';

                public function getVersion(string $output, Website $website): ?string
                {
                    return filter_var($website->getDocumentRoot(), FILTER_VALIDATE_URL) ? Website::VERSION_UNKNOWN : null;
                }
            },

            // Drupal (multisite)
            new class(Website::TYPE_DRUPAL_MULTISITE) extends AbstractDetector {
                public function getCommand(Website $website): string
                {
                    $siteDirectory = 'sites/'.$website->getDomain();

                    return "[ -e $siteDirectory ] && cd $siteDirectory && hash drush 2>/dev/null && drush status --format=json";
                }

                public function getVersion(string $output, Website $website): ?string
                {
                    $data = $this->parseJson($output);

                    return $data['drupal-version'] ?? null;
                }
            },

            // Drupal
            new class(Website::TYPE_DRUPAL) extends AbstractDetector {
                protected $command = 'hash drush 2>/dev/null && drush status --format=json';

                public function getVersion(string $output, Website $website): ?string
                {
                    $data = $this->parseJson($output);

                    return $data['drupal-version'] ?? null;
                }
            },

            // Symfony 4 and 5
            new class(Website::TYPE_SYMFONY) extends AbstractDetector {
                protected $command = '[ -e ../bin/console ] && APP_ENV=prod ../bin/console --version 2>/dev/null';

                public function getVersion(string $output, Website $website): ?string
                {
                    return preg_match('/symfony\s+(?<version>\S+)/i', $output, $matches) ? $matches['version'] : null;
                }
            },

            // Symfony 3
            new class(Website::TYPE_SYMFONY) extends AbstractDetector {
                protected $command = '[ -e ../bin/console ] && ../bin/console --env=prod --version 2>/dev/null';

                public function getVersion(string $output, Website $website): ?string
                {
                    return preg_match('/symfony\s+(?<version>\S+)/i', $output, $matches) ? $matches['version'] : null;
                }
            },

            // Symfony 2
            new class(Website::TYPE_SYMFONY) extends AbstractDetector {
                protected $command = '[ -e ../app/console ] && ../app/console --env=prod --version 2>/dev/null';

                public function getVersion(string $output, Website $website): ?string
                {
                    return preg_match('/version\s+(?<version>\S+)/i', $output, $matches) ? $matches['version'] : null;
                }
            },

            // Unknown
            new class(Website::TYPE_UNKNOWN) extends AbstractDetector {
                protected $command = 'true';

                public function getVersion(string $output, Website $website): ?string
                {
                    return Website::VERSION_UNKNOWN;
                }
            },
        ];
    }
}
