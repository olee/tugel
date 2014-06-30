<?php

namespace Zeutzheim\PpintBundle\Model;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;
use Zeutzheim\PpintBundle\Entity\Language;
use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

class Crawler extends ContainerAwareHelperNT {

	private $packageQb;
	private $versionQb;

	/**
	 * @var Logger
	 */
	public $logger;
	private $logProgress;

	public function __construct($container, EntityManager $em, Logger $logger) {
		parent::__construct($container);
		$this->logger = $logger;
		$this->em = $em;
	}

	//*******************************************************************

	public function getDemoPlatform() {
		$platform = $this->getPlatform('packagist');
		if (!$platform) {
			// Create demo platform
			$platform = new Platform();
			$platform->setName('packagist');
			$platform->setBaseUrl('https://packagist.org/packages/');
			$platform->setCrawlUrl('https://packagist.org/feeds/releases.rss');
			$platform->setPackageRegex('@<link>https://packagist.org/packages/([^<]*)</link>@i');
			$platform->setVersionRegex('@<li (?=[^>]*class="version[^>]*).*id="([^"]*)"@i');
			$platform->setMasterVersion('dev-master');
			$this->getEntityManager()->persist($platform);

			$lang = $this->getLanguage('php');
			if (!$lang) {
				$lang = new Language();
				$lang->setName('php');
				$lang->setExtension('.php');
				$this->getEntityManager()->persist($lang);
			}
			$platform->addLanguage($lang);
			$this->getEntityManager()->flush();
		}

		$platform = $this->getPlatform('hackage');
		if (!$platform) {
			// Create demo platform
			$platform = new Platform();
			$platform->setName('hackage');
			$platform->setBaseUrl('https://hackage.haskell.org/package/');
			$platform->setCrawlUrl('https://hackage.haskell.org/packages/recent.rss');
			$platform->setPackageRegex('@<link>http://hackage.haskell.org/package/([^<]*)-[\d\.]*</link>@i');
			$platform->setVersionRegex('@/package/[^"]*-([\d\.]*)"@i');
			$this->getEntityManager()->persist($platform);

			$lang = $this->getLanguage('haskell');
			if (!$lang) {
				$lang = new Language();
				$lang->setName('haskell');
				$lang->setExtension('.hs');
				$this->getEntityManager()->persist($lang);
			}
			$platform->addLanguage($lang);
			$this->getEntityManager()->flush();
		}
		//$platform->setCrawlUrl('https://hackage.haskell.org/packages/');
		//$platform->setPackageRegex('@/package/([^"]*)-?[\d\.]*"@i');

		$platform->setCrawlUrl('https://hackage.haskell.org/packages/recent.rss');
		$platform->setPackageRegex('@<link>http://hackage.haskell.org/package/([^<]*)-[\d\.]*</link>@i');

		return $platform;
	}

	//*******************************************************************

	public function crawlPlatforms($count = 0) {
		$this->log('---- started crawling platforms ----');
		set_time_limit(60 * 60);
		$platform = $this->getDemoPlatform();
		foreach ($this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Platform')->findAll() as $platform) {
			$this->crawlPlatform($platform);
			if ($count == 1)
				break;
			$count--;
		}
		$this->log('---- finished crawling platforms ----');
	}

	public function crawlPlatform(Platform $platform) {
		$this->log('---- started crawling platform ' . $platform->getName() . ' ----');
		$platform = $this->getEntityManager()->merge($platform);

		$src = $this->httpGet($platform->getCrawlUrl());

		preg_match_all($platform->getPackageRegex(), $src, $matches);
		$packages = array_unique($matches[1]);

		foreach ($packages as $packageUri) {
			$package = $this->getPackage($platform, $packageUri);
			if (!$package) {
				$package = new Package();
				$package->setName($packageUri);
				$package->setUrl($packageUri);
				$package->setPlatform($platform);
				$this->getEntityManager()->persist($package);

				$this->log('A ' . $package->getPlatform()->getName() . ' ' . $package->getName());
			} else {
				$package->setCrawled(false);
				$this->log('U ' . $package->getPlatform()->getName() . ' ' . $package->getName());
			}
		}
		$this->getEntityManager()->flush();
		$this->log('---- finished crawling platform ' . $platform->getName() . ' ----');
	}

	//*******************************************************************

	public function crawlPackages(Platform $platform = null, $maxCount = 0) {
		$this->log('---- started crawling packages' . ($platform ? ' of platform ' . $package->getPlatform()->getName() : '') . ' ----');
		
		// Load package list
		$packages = $this->selectCrawlPackages($platform);
		
		// Calculate maximum number of iterations
		if ($maxCount == 0)
			$maxCount = count($packages);
		else
			$maxCount = min(count($packages), $maxCount);
		set_time_limit(60 * 5 + $maxCount);
		
		// Remove Package-entities from UnitOfWork which may be left from previous 
		// operations to speed up flushing process
		$this->getEntityManager()->clear('ZeutzheimPpintBundle:Package');
		
		// Crawl packages
		for ($i = 0; $i < $maxCount; $i++) {
			$this->logProgress = $i . '/' . $maxCount . ' ' . round($i * 100 / $maxCount) . '% ';
			$this->crawlPackage($this->getEntityManager()->getReference('ZeutzheimPpintBundle:Package', $packages[$i]), false);
		}
		
		$this->logProgress = null;
		$this->log('---- finished crawling packages' . ($platform ? ' of platform ' . $package->getPlatform()->getName() : '') . ' ----');
	}

	public function crawlPackage(Package $package = null) {
		// If no package is passed, pick one to crawl
		if (!$package) {
			$package = $this->selectCrawlPackage();
			if (!$package)
				return false;
		}
		
		// Clear UnitOfWork to speed up flushing of entities when too large
		if ($this->getManagedEntityCount() > 60)
			$this->getEntityManager()->clear('ZeutzheimPpintBundle:Version');
		
		// Load source
		$src = $this->httpGet($package->getPlatform()->getBaseUrl() . $package->getName());
		if ($src == null)
			return false;
		
		// Find versions from source
		preg_match_all($package->getPlatform()->getVersionRegex(), $src, $matches);
		// Make version list unique
		$versions = array_unique($matches[1]);

		foreach ($versions as $versionName) {
			// Add version to package
			$version = $this->getVersion($package, $versionName);
			$newVersion = $version == null;
			if ($newVersion) {
				$version = new Version();
				$version->setName($versionName);
				$version->setPackage($package);
				$this->getEntityManager()->persist($version);
	
				$this->log('AV ' . $package->getPlatform()->getName() . ' ' . $package->getName() . ' ' . $version->getName());
				//sleep(1);
			}
			
			// Check if a master-version was found
			if ($version->getName() == $package->getPlatform()->getMasterVersion() && $package->getPlatform()->getMasterVersionTagRegex()) {
				// Fetch the master-version identifiert (hash, date, etc.)
				if (preg_match($package->getPlatform()->getMasterVersionTagRegex(), $src, $matches)) {
					// Check if the master-version is still up to date
					if ($package->getMasterVersionTag() != $matches[1]) {
						$package->setMasterVersionTag($matches[1]);
						if (!$newVersion) {
							$version->setCrawled(false);
							$this->log('UV ' . $package->getPlatform()->getName() . ' ' . $package->getName() . ' ' . $version->getName());
						}
					}
				}
			}
		}
		$package->setCrawled(true);
		
		// Flush changes
		$this->getEntityManager()->flush();
		
		return true;
	}

	public function log($msg) {
		if ($this->logProgress)
			$msg = $this->logProgress . $msg;
		$this->logger->info($msg);
	}

	//*******************************************************************

	/**
	 * @return Package
	 */
	public function selectCrawlPackage(Platform $platform = null) {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->where('pkg.crawled = FALSE')->orderBy('pkg.addedDate')->setMaxResults(1);
		if ($platform)
			$qb->andWhere('pkg.platform = ' . $platform->getId());
		return $qb->getQuery()->getOneOrNullResult();
	}

	/**
	 * @return array (integer)
	 */
	public function selectCrawlPackages(Platform $platform = null) {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->select('pkg.id')->where('pkg.crawled = FALSE')->orderBy('pkg.addedDate');
		if ($platform)
			$qb->andWhere('pkg.platform = ' . $platform->getId());
		return $qb->getQuery()->getResult();
	}

	//*******************************************************************
	
	public function getManagedEntityCount() {
		$count = 0;
        foreach ($this->getEntityManager()->getUnitOfWork()->getIdentityMap() as $entities) $count += count($entities);
		return $count;
	}
	
	public function getPlatform($name) {
		return $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Platform')->findOneByName($name);
	}

	public function getLanguage($name, $ext = null) {
		return $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Language')->findOneByName($name);
	}

	public function getPackage(Platform $platform, $url) {
		if (!$this->packageQb) {
			$this->packageQb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('e');
			$this->packageQb->where('e.platform = ?1');
			$this->packageQb->andWhere('e.url = ?2');
		}
		return $this->packageQb->setParameters(array(
			1 => $platform->getId(),
			2 => $url
		))->getQuery()->getOneOrNullResult();
	}

	public function getVersion(Package $package, $name) {
		if (!$this->versionQb) {
			$this->versionQb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Version')->createQueryBuilder('v');
			$this->versionQb->where('v.package = ?1');
			$this->versionQb->andWhere('v.name = ?2');
		}
		return $this->versionQb->setParameters(array(
			1 => $package->getId(),
			2 => $name
		))->getQuery()->getOneOrNullResult();
	}

	private function httpGet($url) {
		/*
		 $ch = curl_init();
		 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		 curl_setopt($ch, CURLOPT_URL, $url);
		 $result = curl_exec($ch);
		 curl_close($ch);
		 return $result;
		 //*/
		//*
		$file = fopen($url, "rb");
		if (!$file)
			return false;
		$result = '';
		while (!feof($file))
			$result .= fread($file, 1024 * 8);
		fclose($file);
		return $result;
		//*/
	}

}
