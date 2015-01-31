<?php

namespace Tugel\TugelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Tugel\TugelBundle\Model\PackageManager;

class ResetCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('tugel:reset');
		$this->setDefinition(array(
			new InputArgument('platform', InputArgument::OPTIONAL, 'The platform'),
		));
		$this->addOption('clear', null, InputOption::VALUE_NONE, 'Completly clear index');
		$this->addOption('force', null, InputOption::VALUE_NONE, 'Force reindexing (even if version is the same)');
		$this->addOption('errors', null, InputOption::VALUE_NONE, 'Reset errors');
		$this->setDescription("Reset index / reindex");
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		/** 
		 * @var PackageManager
		 */
		$tugel = $this->getContainer()->get('tugel.package_manager');
		$tugel->resetIndex($input->getArgument('platform'), $input->getOption('clear'), $input->getOption('errors'), $input->getOption('force'));
	}

}
