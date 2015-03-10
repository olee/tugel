<?php

namespace Tugel\TugelBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Tugel\TugelBundle\Entity\Package;

use Tugel\TugelBundle\Model\AbstractPlatform;

class InitialIndexCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('tugel:init');
		$this->setDefinition(array(new InputArgument('platform', InputArgument::REQUIRED, 'The platform to index'), ));
		$this->setDescription("Run initial indexing");
	}

	/**
	 * @return Tugel\TugelBundle\Model\AbstractPlatform
	 */
	public function getPlatform($name) {
		return $this->getContainer()->get('tugel.platform.' . $name);
	}

	protected function indexPackagist() {
		$platform = $this->getPlatform('packagist');
		if (!$platform)
			throw new \RuntimeException();

		$packages = json_decode(file_get_contents('packagist.json'));
		if (!$packages) {
			$platform->log('error adding packages', $platform, Logger::WARNING);
			return;
		}

		$platform->log('started adding packages', $platform);

		$cnt = 0;
		foreach ($packages->packageNames as $packageUri) {
			$package = $platform->getPackage($packageUri);
			if (!$package) {
				$package = new Package();
				$package->setName($packageUri);
				$package->setPlatform($platform->getPlatformReference());
				$platform->getEntityManager()->persist($package);

				$platform->log('Package added', $package);
				if ($cnt++ > 200) {
					$platform->getEntityManager()->flush();
					$platform->getEntityManager()->clear();
					$cnt = 0;
				}
			} else {
				//$platform->log('Package already exists', $package);
			}
		}
		$platform->getEntityManager()->flush();
		$platform->log('finished adding packages', $platform);
	}

	protected function indexPypi() {
		$platform = $this->getPlatform('pypi');
		if (!$platform)
			throw new \RuntimeException();

		$packages = file_get_contents('pypi.txt');
		if (!$packages) {
			$platform->log('started adding packages', $platform, Logger::WARNING);
			return;
		}

		$platform->log('started adding packages', $platform);

		$cnt = 0;
		foreach (explode("\n", str_replace("\r", '', $packages)) as $packageUri) {
			$package = $platform->getPackage($packageUri);
			if (!$package) {
				$package = new Package();
				$package->setName($packageUri);
				$package->setPlatform($platform->getPlatformReference());
				$platform->getEntityManager()->persist($package);

				$platform->log('Package added', $package);
				if ($cnt++ > 100) {
					$platform->getEntityManager()->flush();
					$platform->getEntityManager()->clear();
					$cnt = 0;
				}
			}
		}
		$platform->getEntityManager()->flush();
		$platform->log('finished adding packages', $platform);
	}

	protected function indexHackage() {
		$platform = $this->getPlatform('hackage');
		if (!$platform)
			throw new \RuntimeException();

		$packages = file_get_contents('hackage.txt');
		if (!$packages) {
			$platform->log('started adding packages', $platform, Logger::WARNING);
			return;
		}

		$platform->log('started adding packages', $platform);

		$cnt = 0;
		foreach (explode("\n", str_replace("\r", '', $packages)) as $packageUri) {
			$package = $platform->getPackage($packageUri);
			if (!$package) {
				$package = new Package();
				$package->setName($packageUri);
				$package->setPlatform($platform->getPlatformReference());
				$platform->getEntityManager()->persist($package);

				$platform->log('Package added', $package);
				if ($cnt++ > 100) {
					$platform->getEntityManager()->flush();
					$platform->getEntityManager()->clear();
					$cnt = 0;
				}
			}
		}
		$platform->getEntityManager()->flush();
		$platform->log('finished adding packages', $platform);
	}

	protected function indexMaven() {
		$platform = $this->getPlatform('maven');
		if (!$platform)
			throw new \RuntimeException();

		$platform->log('started crawling', $platform);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', ));

		set_time_limit(60 * 60);

		$rootPackages = array(//
		'org', 'com', //
		);
		foreach ($rootPackages as &$rootPackage) {
			$platform->log('Namespace ' . $rootPackage . '.*', $platform);
			$index = 0;
			while (true) {
				$hasNewPackages = false;

				$platform->log('Namespace ' . $rootPackage . '.* index ' . $index, $platform);

				curl_setopt($ch, CURLOPT_URL, 'http://search.maven.org/solrsearch/select?wt=json&q=g:' . $rootPackage . '.*&rows=200&start=' . $index);
				$src = curl_exec($ch);
				$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($status != 200) {
					$platform->log('Error getting packages', $platform);
					return;
				}

				$data = json_decode($src);
				if (!$data) {
					$platform->log('Error getting packages', $platform);
					return;
				}

				if (empty($data->response->docs)) {
					$platform->log('Finished root package ' . $rootPackage, $platform);
					break;
				}

				foreach ($data->response->docs as &$packageData) {
					$packageUri = $packageData->id;
					$package = $platform->getPackage($packageUri);
					if (!$package) {
						$package = new Package();
						$package->setName($packageUri);
						$package->setPlatform($platform->getPlatformReference());
						$platform->getEntityManager()->persist($package);

						$platform->log('Package added', $package);
					} else {
						//$package->setNew(true);
						//$platform->log('Package updated', $package);
					}
					$index++;
				}

				$platform->getEntityManager()->flush();
				$platform->getEntityManager()->clear();
			}
		}
		curl_close($ch);

		//$platform->getEntityManager()->flush();
		$platform->log('finished adding packages', $platform);
	}

	/**
	 * @see Command
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		switch ($input->getArgument('platform')) {
			case 'packagist' :
				$this->indexPackagist();
				break;
			case 'pypi' :
				$this->indexPypi();
				break;
			case 'hackage' :
				$this->indexHackage();
				break;
			case 'maven' :
				$this->indexMaven();
				break;
			default :
				$this->getContainer()->get('tugel.logger')->info('This platform has no index initializer');
				break;
		}
	}

}
