<?php

namespace Tugel\TugelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Tugel\TugelBundle\Util\Utils;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

class TestCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('tugel:test');
		/*
		 $this->setDefinition(array(
		 new InputArgument('platform', InputArgument::OPTIONAL, 'The platform the package is part of'),
		 new InputArgument('package', InputArgument::OPTIONAL, 'The package which should be scanned'),
		 new InputArgument('version', InputArgument::OPTIONAL, 'The version which should be scanned'),
		 ));
		 $this->addOption('maxtime', 't', InputOption::VALUE_OPTIONAL, 'The maximum execution time', 60);
		 $this->addOption('cachesize', 'c', InputOption::VALUE_NONE, 'Print the cachesize on start');
		 $this->addOption('redownloadmaster', 'r', InputOption::VALUE_NONE, 'Redownload master-versions');
		 $this->setDescription("Analyze one or more packages and add them to the index.\n
		 The command can index all packages, all packages of a platform or a single package, depending on the arguments specified.\n
		 If the version is not specified, the command tries to pick the newest version.");
		 */
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$em = $this->getContainer()->get('doctrine.orm.entity_manager');
		{
			$license = 'The MIT License (MIT)';

			$license = str_replace('-', ' ', $license);
			$license = preg_replace('/(^|[^\w\n\/]|\d)((?:' . join(')|(?:', array('style', 'clause', 'license', 'version', 'the')) . '))(?=[^\w\.]|$)/mi', '$1', $license);
			$license = preg_replace('/\s*v\s*([\d\.]+)/', ' $1', $license);
			$license = preg_replace('/([^\d\s\.])(\d)/', '$1 $2', $license);
			$license = preg_replace('/\s*,\s*\n/', '\n', $license);
			$license = preg_replace('@\n.*@', '', $license);
			$license = preg_replace('/\s*v\s*([\d\.]+)(?=[^\w])/', ' $1', $license);
			$license = preg_replace('/(\s|^)\s+/', '$1', $license);
			echo $license . "\n";
			//exit;
		}

		$logger = $this->getContainer()->get('logger');
		$i = 0;
		$cache = array();
		foreach ($em->getRepository('TugelBundle:Package')->createQueryBuilder('pkg')->getQuery()->iterate() as $package) {
			$package = $package[0];

			$license = $package->getLicense();
			if (isset($cache[$license])) {
				$license = $cache[$license];
			} else {
				$license = str_replace('-', ' ', $license);
				$license = preg_replace('/(^|[^\w\n\/]|\d)((?:' . join(')|(?:', array('style', 'clause', 'license', 'version', 'the')) . '))(?=[^\w\.]|$)/mi', '$1', $license);
				$license = preg_replace('/\s*v\s*([\d\.]+)/', ' $1', $license);
				$license = preg_replace('/([^\d\s\.])(\d)/', '$1 $2', $license);
				$license = preg_replace('/\s*,\s*\n/', '\n', $license);
				$license = preg_replace('@\n.*@', '', $license);
				$license = preg_replace('/\s*v\s*([\d\.]+)(?=[^\w])/', ' $1', $license);
				$license = preg_replace('/(\s|^)\s+/', '$1', $license);
				$cache[$license] = $license;
			}
			$package->setLicense(trim($license));

			if ((++$i % 1000) === 0) {
				$logger->info('Batch processing: ' . $i);
				$em->flush();
				$em->clear();
			}
		}
		$em->flush();
	}

}
