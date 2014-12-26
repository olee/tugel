<?php

namespace Zeutzheim\PpintBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Zeutzheim\PpintBundle\Entity\Package;

use Zeutzheim\PpintBundle\Model\AbstractPlatform;

class InitialIndexCommand extends ContainerAwareCommand {
	/**
	 * @see Command
	 */
	protected function configure() {
		parent::configure();

		$this->setName('ppint:init');
		$this->setDefinition(array(new InputArgument('platform', InputArgument::REQUIRED, 'The platform to index'), ));
		$this->setDescription("Run initial indexing");
	}

	/**
	 * @return Zeutzheim\PpintBundle\Model\AbstractPlatform
	 */
	public function getPlatform($name) {
		return $this->getContainer()->get('ppint.platform.' . $name);
	}

	protected function indexPackagist() {
		$platform = $this->getPlatform('packagist');
		if (!$platform)
			throw new \RuntimeException();

		$platform->log('started crawling', $platform);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest', ));

		set_time_limit(60 * 60);

		$letter = 'a';
		do {
			for ($page = 1; $page <= 200; $page++) {
				$platform->log('LETTER ' . $letter . ' PAGE ' . $page, $platform);

				curl_setopt($ch, CURLOPT_URL, 'https://packagist.org/search/?search_query%5Bquery%5D=' . $letter . '&page=' . $page);
				$src = curl_exec($ch);
				$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($status != 200)
					break;

				preg_match_all('@/packages/([^"]*)"@i', $src, $matches);
				$packages = array_unique($matches[1]);

				//$platform->getEntityManager()->merge($platform->getPlatformReference());

				foreach ($packages as $packageUri) {
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
				}

				$platform->getEntityManager()->flush();
				$platform->getEntityManager()->clear();
			}
			$letter++;
		} while ($letter != 'z');
		curl_close($ch);

		//$platform->getEntityManager()->flush();
		$platform->log('finished crawling', $platform);
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
		$platform->log('finished crawling', $platform);
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
		$platform->log('finished crawling', $platform);
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
			default :
				$this->getContainer()->get('ppint.logger')->info('This platform has no index initializer');
				break;
		}
	}

}
