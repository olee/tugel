<?php

namespace Zeutzheim\PpintBundle\Command;

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

		$this->setName('ppint:find');
		$this->setDefinition(array(
			new InputArgument('filename', InputArgument::REQUIRED),
		));
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$ppint = $this->getContainer()->get('ppint.manager');
		$ppint->findPackagesBySource($input->getArgument('filename'));
	}

}
