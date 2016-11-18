<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Website;

class DetectCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('itksites:detect')
            ->setDescription('Detect type of all sites');
    }

    protected function runCommand()
    {
        $websites = $this->getWebsites();

        $detectors = [
            'drupal (multisite)' => [
                'getCommand' => function (Website $website) {
                    $siteDirectory = 'sites/' . $website->getDomain();
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

            $cmdTemplate = 'ssh  -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@' . $website->getServer()
                                     . ' "cd ' . $website->getDocumentRoot() . ' && {{ command }}"';

            foreach ($detectors as $type => $detector) {
                $command = isset($detector['getCommand']) ? $detector['getCommand']($website) : $detector['command'];
                $cmd = str_replace('{{ command }}', $command, $cmdTemplate);

                $output = null;
                $code = 0;

                @exec($cmd, $output, $code);
                if ($code == 0) {
                    $version = $detector['getVersion']($output, $website);
                    if ($version !== null) {
                        $website
                            ->setType(isset($detector['type']) ? $detector['type'] : $type)
                            ->setVersion($version);
                        $this->persist($website);

                        $this->output->writeln(implode("\t", [ $website->getDomain(), $website->getType(), $website->getVersion() ]));
                        break;
                    }
                }
            }
        }
    }
}
