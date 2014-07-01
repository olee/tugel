<?php

namespace Zeutzheim\PpintBundle\Model;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;
use Zeutzheim\PpintBundle\Entity\Language;
use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

abstract class Crawler extends ContainerAwareHelperNT {
	
	protected $platform;
	protected $platformRef;

	protected $packageQb;
	protected $versionQb;

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
	
	public function crawlPlatform() {
		$this->log('---- started crawling platform ' . $this->getPlatformName() . ' ----');

		$src = $this->httpGet($this->getCrawlUrl());

		preg_match_all($this->getPackageRegex(), $src, $matches);
		$packages = array_unique($matches[1]);

		foreach ($packages as $packageUri) {
			$package = $this->getPackage($packageUri);
			if (!$package) {
				$package = new Package();
				$package->setName($packageUri);
				$package->setUrl($packageUri);
				$package->setPlatform($this->getPlatformReference());
				$this->getEntityManager()->persist($package);

				$this->log('A ' . $this->getPlatformName() . ' ' . $package->getName());
			} else {
				$package->setCrawled(false);
				$this->log('U ' . $this->getPlatformName() . ' ' . $package->getName());
			}
		}
		$this->getEntityManager()->flush();
		$this->getEntityManager()->clear('ZeutzheimPpintBundle:Package');
		$this->log('---- finished crawling platform ' . $this->getPlatformName() . ' ----');
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
		$src = $this->httpGet($this->getBaseUrl() . $package->getName());
		if ($src == null)
			return false;
		
		// Find versions from source
		preg_match_all($this->getVersionRegex(), $src, $matches);
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
	
				$this->log('AV ' . $this->getPlatformName() . ' ' . $package->getName() . ' ' . $version->getName());
				//sleep(1);
			}
			
			// Check if a master-version was found
			if ($version->getName() == $this->getPlatform()->getMasterVersion() && $this->getMasterVersionTagRegex()) {
				// Fetch the master-version identifiert (hash, date, etc.)
				if (preg_match($this->getMasterVersionTagRegex(), $src, $matches)) {
					// Check if the master-version is still up to date
					if ($package->getMasterVersionTag() != $matches[1]) {
						$package->setMasterVersionTag($matches[1]);
						if (!$newVersion) {
							$version->setCrawled(false);
							$this->log('UV ' . $this->getPlatformName() . ' ' . $package->getName() . ' ' . $version->getName());
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

	//*******************************************************************
	
	public abstract function downloadVersion(Version $version, $path);

	public abstract function setupPlatform(Platform $platform);
	
	//*******************************************************************
	
	public abstract function getPlatformName();
	
	public abstract function getBaseUrl();
	
	public abstract function getCrawlUrl();
	
	public abstract function getPackageRegex();
	
	public abstract function getVersionRegex();
	
	public abstract function getMasterVersion();
	
	public abstract function getMasterVersionTagRegex();

	//*******************************************************************
	
	/**
	 * @return array (integer)
	 */
	public function selectCrawlPackages() {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->select('pkg.id')->where('pkg.crawled = FALSE')->andWhere('pkg.platform = ' . $this->getPlatform()->getId())->orderBy('pkg.addedDate');
		return $qb->getQuery()->getResult();
	}

	/**
	 * @return Platform
	 */
	public function getPlatform() {
		if (!$this->platform) {
			$this->platform = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Platform')->findOneByName($this->getPlatformName());
			if (!$this->platform) {
				$this->platform = new Platform();
				$this->platform->setName($this->getPlatformName());
				$this->setupPlatform($this->platform);
				$this->getEntityManager()->persist($this->platform);
				$this->getEntityManager()->flush();
			}
		}
		return $this->platform;
	}
		
	/**
	 * @return Platform
	 */
	public function getPlatformReference() {
		if (!$this->platformRef) {
			$this->platformRef = $this->getEntityManager()->getReference('ZeutzheimPpintBundle:Platform', $this->getPlatform()->getId());
		}
		return $this->platformRef;
	}
	
	public function getManagedEntityCount() {
		$count = 0;
        foreach ($this->getEntityManager()->getUnitOfWork()->getIdentityMap() as $entities) $count += count($entities);
		return $count;
	}
	
	public function getLanguage($name) {
		return $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Language')->findOneByName($name);
	}

	public function getPackage($url) {
		if (!$this->packageQb) {
			$this->packageQb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('e');
			$this->packageQb->where('e.platform = ?1');
			$this->packageQb->andWhere('e.url = ?2');
		}
		return $this->packageQb->setParameters(array(
			1 => $this->getPlatform()->getId(),
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

	public function getLatestVersion(Package $package) {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Version')->createQueryBuilder('v');
		return $qb->where('v.package = ' . $package->getId())->orderBy('v.addedDate')->setMaxResults(1)->getQuery()->getOneOrNullResult();
	}

	public function httpGet($url) {
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

	public function log($msg) {
		if ($this->logProgress)
			$msg = $this->logProgress . $msg;
		$this->logger->info($msg);
	}

	//*******************************************************************
}
