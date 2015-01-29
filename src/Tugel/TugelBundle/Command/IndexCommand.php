<?php

namespace Tugel\TugelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Tugel\TugelBundle\Model\PackageManager;

class IndexCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('tugel:index');
		$this->setDefinition(array(
			new InputArgument('platform', InputArgument::OPTIONAL, 'The platform the package is part of'),
			new InputArgument('package', InputArgument::OPTIONAL, 'The package which should be scanned'),
		));
		$this->addOption('maxtime', 't', InputOption::VALUE_OPTIONAL, 'The maximum execution time', 60 * 10);
		$this->addOption('cachesize', null, InputOption::VALUE_NONE, 'Print the cachesize on start');
		$this->addOption('quick', null, InputOption::VALUE_NONE, 'Quick indexing without checking for new versions');
		$this->addOption('dry', 'd', InputOption::VALUE_NONE, 'Only do a dry-run');
		$this->setDescription("Analyze one or more packages and add them to the index.\n
The command can index all packages, all packages of a platform or a single package, depending on the arguments specified.\n
If the version is not specified, the command tries to pick the newest version.");
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		//echo "Result = " . $this->httpGet('https://api.github.com/repos/geissler/converter' . '?client_id=6e91ea3626b5a9ff12bf&client_secret=a182ddb9a7b008e3395f8e743e3944fcccb178e7') . "\n"; exit;
		/** 
		 * @var PackageManager
		 */
		$tugel = $this->getContainer()->get('tugel.package_manager');
		
		if ($tugel->index(
			$input->getArgument('platform'), 
			$input->getArgument('package'), 
			$input->getOption('maxtime'), 
			$input->getOption('cachesize'), 
			$input->getOption('quick'), 
			$input->getOption('dry')))
		{
			$this->getContainer()->get('tugel.logger')->info('> Successfully indexd packages.');
		} else {
			$this->getContainer()->get('tugel.logger')->info('> Error while analyzing packages!');
		}
	}

}
