<?php

namespace Tugel\TugelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class FindCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();
		$this->setName('tugel:find');
		$this->setDefinition(array(
			new InputArgument('filename', InputArgument::REQUIRED),
		));
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$tugel = $this->getContainer()->get('tugel.package_manager');
		$tugel->findPackagesBySource($input->getArgument('filename'));
	}

}
