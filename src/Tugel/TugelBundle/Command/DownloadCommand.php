<?php

namespace Tugel\TugelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class DownloadCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('tugel:download');
		$this->setDefinition(array(
			new InputArgument('platform', InputArgument::REQUIRED, 'The platform the package is part of'),
			new InputArgument('package', InputArgument::REQUIRED, 'The package which should be downloaded'),
			new InputArgument('version', InputArgument::OPTIONAL, 'The version which should be downloaded'),
			new InputArgument('path', InputArgument::OPTIONAL, 'The destination path'),
		));
		//$this->setDescription('Generates sample data');
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$tugel = $this->getContainer()->get('tugel.package_manager');
		if ($tugel->download($input->getArgument('platform'), $input->getArgument('package'), $input->getArgument('version'), $input->getArgument('path'))) {
			$this->getContainer()->get('tugel.logger')->info("Successfully downloaded package.");
		} else {
			$this->getContainer()->get('tugel.logger')->info("Error while downloaded package!");
		}
	}

}
