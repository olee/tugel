<?php

namespace Zeutzheim\PpintBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CrawlCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('ppint:crawl');
		//$this->setDescription('Generates sample data');
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$crawler = $this->getContainer()->get('ppint.crawler');
		$crawler->crawlPlatforms();
		$crawler->crawlPackages();
	}

	/**
	 * @see Command
	 */
	protected function interact(InputInterface $input, OutputInterface $output) {
	}

}
