<?php

namespace Zeutzheim\PpintBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Zeutzheim\PpintBundle\Util\Utils;

use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;

class TestCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('ppint:test');
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
        
        /**
         * @var Platform
         */
        $platform = $this->getContainer()->get('ppint.platform.pypi');
        
        $src = file_get_contents('PyPi-index.txt');
        foreach (explode(PHP_EOL, $src) as $name) {
            $package = new Package();
            $package->setName($name);
            $package->setUrl($name);
            $package->setPlatform($platform->getPlatformReference());
            $em->persist($package);
        }
        
        
        $em->flush();
    }

}
