<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Website;

class ServerOverviewCommand extends ContainerAwareCommand {
  protected function configure() {
    $this
      ->setName('server-overview')
      ->setDescription('Server overview')
      ->addArgument(
        'action',
        InputArgument::REQUIRED,
        'What do you want to do? (dump|update|detect|data)'
      );
  }

  private $output;
  private $em;
  private $repo;

  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->output = $output;
    $this->em = $this->getContainer()->get('doctrine')->getEntityManager('default');
    $this->repo = $this->em->getRepository('AppBundle:Website');

    $action = $input->getArgument('action');

    if (method_exists($this, $action)) {
      $this->{$action}($input);
    } else {
      throw new \Exception('Invalid action: ' . $action);
    }
  }

  private function dump() {
    $websites = $this->getWebsites();
    echo '#websites: ' . count($websites), PHP_EOL;
    foreach ($websites as $website) {
      echo $website->getId();
    }
  }

    private function update() {
    $serverNames = $this->getServerNames();

    foreach ($serverNames as $serverName) {
      $this->writeln($serverName);
      // $cmd = 'ssh -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@' . $serverName . ' "for f in \$(sudo /usr/sbin/apachectl  -t -D DUMP_VHOSTS | grep namevhost | sed \'s/^.*namevhost *\([^ ]*\) *(\([^:)]*\).*$/\2/\'); do echo --- \$f; grep \'^[[:space:]]*\(Server\(Name\|Alias\)\|DocumentRoot\)\' \$f; done"';
      $cmd = 'ssh -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@' . $serverName . ' "for f in /etc/{apache,nginx}*/sites-enabled/*; do echo --- \$f; [ -e $f ] && grep --no-messages \'^[[:space:]]*\(server_name\|root\|Server\(Name\|Alias\)\|DocumentRoot\)\' \$f; done"';
      $lines = [];
      $code = 0;
      exec($cmd, $lines, $code);
      if (!empty($lines)) {
        $lines = array_map(function($line) { return trim($line); }, $lines);
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
              } else if (preg_match('/^(?:server_name|Server(?:Name|Alias))\s+(.+)/', $line, $matches)) {
                if (!$domains) {
                  $domains = [];
                }
                $domains[] = trim($matches[1], ';');
              } else if (preg_match('/^(?:root|DocumentRoot)\s+(.+)/', $line, $matches)) {
                if ($domains) {
                  $documentRoot = trim($matches[1], ';');

                  foreach ($domains as $domain) {
                    $website = $this->getWebsite([ 'domain' => $domain ]);
                    if (!$website) {
                      $website = new \AppBundle\Entity\Website();
                    }
                    $website
                      ->setDomain($domain)
                      ->setServer($serverName)
                      ->setDocumentRoot($documentRoot);

                    $this->writeln('  ' . $website); //->getDomain());

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

  private function detect() {
    $websites = $this->getWebsites();

    $detectors = [
      'drupal (multisite)' => [
        'getCommand' => function(Website $website) {
          $siteDirectory = 'sites/' . $website->getDomain();
          return "[ -e $siteDirectory ] && cd $siteDirectory && hash drush 2>/dev/null && drush status --format=json";
        },
        'getVersion' => function(array $output) {
          $data = json_decode(implode('', $output), true);
          return isset($data['drupal-version']) ? $data['drupal-version'] : null;
        },
      ],
      'drupal' => [
        'command' => 'hash drush 2>/dev/null && drush status --format=json',
        'getVersion' => function(array $output) {
          $data = json_decode(implode('', $output), true);
          return isset($data['drupal-version']) ? $data['drupal-version'] : null;
        },
      ],
      'symfony' => [
        'command' => '[ -e ../app/console ] && ../app/console --version 2>/dev/null',
        'getVersion' => function(array $output) {
          return preg_match('/version\s+(?<version>[^\s]+)/', $output[0], $matches) ? $matches['version'] : null;
        },
      ],
      'unknown' => [
        'command' => 'true',
        'getVersion' => function(array $output) {
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


  private function data() {
    $websites = $this->getWebsites();

    $detectors = [
      'drupal (multisite)' => [
        'getCommand' => function(Website $website) {
          $siteDirectory = 'sites/' . $website->getDomain();
          return "cd $siteDirectory && drush pm-list --format=json";
        },
        'getData' => function(array $output, Website $website) {
          $data = implode('', $output);
          $modules = json_decode($data, true);
          return json_encode([ 'modules' => $modules ], JSON_PRETTY_PRINT);
        },
      ],
      'drupal' => [
        'command' => 'drush pm-list --format=json',
        'getData' => function(array $output) {
          $data = implode('', $output);
          $modules = json_decode($data, true);
          return json_encode([ 'modules' => $modules ], JSON_PRETTY_PRINT);
        },
      ],
      'symfony' => [
        'command' => 'composer --working-dir=.. show --installed',
        'getData' => function(array $output) {
          $data = implode("\n", $output);
          return $data;
        },
      ],

    ];

    foreach ($websites as $website) {
      $this->output->writeln($website->getDomain());

      if (isset($detectors[$website->getType()])) {
        $this->output->writeln("\t" . $website->getType());

        $detector = $detectors[$website->getType()];

        $cmdTemplate = 'ssh  -o ConnectTimeout=10 -o BatchMode=yes -o StrictHostKeyChecking=no -A deploy@' . $website->getServer()
                     . ' "cd ' . $website->getDocumentRoot() . ' && {{ command }}"';

        $command = isset($detector['getCommand']) ? $detector['getCommand']($website) : $detector['command'];
        $cmd = str_replace('{{ command }}', $command, $cmdTemplate);

        $output = null;
        $code = 0;

        @exec($cmd, $output, $code);
        if ($code == 0) {
          $data = $detector['getData']($output, $website);
          if ($data !== null) {
            $website
              ->setData($data);
            $this->persist($website);
          }
        }
      }
    }
  }

  private function getWebsites() {
    return $this->repo->findAll();
  }

  private function getWebsite($query) {
    $result = $this->repo->findBy($query);
    return (count($result) > 0) ? $result[0] : null;
  }

  private function persist($entity) {
    $this->em->persist($entity);
    $this->em->flush();
  }

  private function writeln() {
    $args = func_get_args();
    call_user_func_array([ $this->output, 'writeln' ], $args);
  }

  private function getServerNames() {
    $serverNames = [];

    $configuration = $this->getContainer()->getParameter('server_list');

    $url = $configuration['url'];
    $type = $configuration['type'];
    $root = $configuration['root'];

    switch ($type) {
      case 'xml':
        $xml = new \SimpleXmlElement($url, null, true);

        $records = [];
        foreach ($xml->xpath($root) as $el) {
          if ($el->code == 200 && preg_match('/^[a-z0-9]+/', $el->server)) {
            $records[] = $el;
          }
        }

        // Get list of unique server names.
        $serverNames = array_values(array_unique(array_map(function($record) {
          return (string)$record->server;
        }, $records)));
    }

    return $serverNames;
  }
}
