<?php

namespace Zeutzheim\PpintBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class IndexCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('ppint:index');
		$this->setDefinition(array(
			new InputArgument('platform', InputArgument::OPTIONAL, 'The platform the package is part of'),
			new InputArgument('package', InputArgument::OPTIONAL, 'The package which should be scanned'),
			new InputArgument('version', InputArgument::OPTIONAL, 'The version which should be scanned'),
		));
		$this->addOption('maxtime', 't', InputOption::VALUE_OPTIONAL, 'The maximum execution time', 60);
		$this->setDescription("Analyze one or more packages and add them to the index.\n
The command can index all packages, all packages of a platform or a single package, depending on the arguments specified.\n
If the version is not specified, the command tries to pick the newest version.");
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		//echo "Result = " . $this->httpGet('https://api.github.com/repos/geissler/converter' . '?client_id=6e91ea3626b5a9ff12bf&client_secret=a182ddb9a7b008e3395f8e743e3944fcccb178e7') . "\n"; exit;
		$ppint = $this->getContainer()->get('ppint.manager');
		if ($ppint->index($input->getArgument('platform'), $input->getArgument('package'), $input->getArgument('version'), $input->getOption('maxtime'))) {
			$this->getContainer()->get('ppint.logger')->info('> Successfully indexd packages.');
		} else {
			$this->getContainer()->get('ppint.logger')->info('> Error while analyzing packages!');
		}
	}

}
