<?php
namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Website;

class DataCommand extends Command {
	protected function configure() {
		$this
			->setName('itksites:data')
			->setDescription('Get data from  all sites')
            ->addOption('types', null, InputOption::VALUE_REQUIRED, 'Website types');
	}

	protected function runCommand() {
		$websites = $this->getWebsites();

		$detectors = [
			'drupal (multisite)' => [
				'getCommand' => function(Website $website) {
					$siteDirectory = 'sites/' . $website->getDomain();
					return "cd $siteDirectory && drush pm-list --format=csv";
				},
				'getData' => function(array $output, Website $website) {
					$data = implode(PHP_EOL, $output);
					return $data;
				},
			],
			'drupal' => [
				'command' => 'drush pm-list --format=csv',
				'getData' => function(array $output) {
					$data = implode(PHP_EOL, $output);
					return $data;
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
}
