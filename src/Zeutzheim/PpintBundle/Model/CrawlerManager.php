<?php

namespace Zeutzheim\PpintBundle\Model;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;

use Zeutzheim\PpintBundle\Model\Crawler;

use Zeutzheim\PpintBundle\Entity\Language;
use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

class CrawlerManager extends ContainerAwareHelperNT {
	
	/**
	 * @var Logger
	 */
	public $logger;

	private $crawlers = array();

	public function __construct($container, Logger $logger) {
		parent::__construct($container);
		$this->logger = $logger;
	}

	public function addCrawler(Crawler $crawler) {
		$this->crawlers[$crawler->getPlatformName()] = $crawler;
	}
	
	//*******************************************************************

	public function crawlPlatforms() {
		$this->logger->info('---- started crawling platforms ----');
		set_time_limit(60 * 60);
		foreach ($this->crawlers as $crawler) {
			$crawler->crawlPlatform();
		}
		$this->logger->info('---- finished crawling platforms ----');
	}
	
	//*******************************************************************
	
	public function crawlPackages($maxCount = 0) {
		$this->logger->info('---- started crawling packages ----');
		
		// Load package list
		$packages = $this->selectCrawlPackages();

		// Calculate maximum number of iterations
		if ($maxCount == 0)
			$maxCount = count($packages);
		else
			$maxCount = min(count($packages), $maxCount);
		set_time_limit(60 * 5 + $maxCount);
		
		foreach ($packages as $package) {
			$this->crawlers[$package->getPlatform()->getName()]->crawlPackage($package);
		}
		
		$this->logger->info('---- finished crawling packages ----');
	}
	
	//*******************************************************************
	
	public function downloadPackage($crawler, $package, $version = null, $path = null) {
		$this->logger->info('---- started downloading version ----');
		
		if (!is_object($crawler)) {
			$crawler = $this->crawlers[$crawler];
		}
		if (!is_object($package)) {
			$package = $crawler->getPackage($package);
		}
		if (!$version) {
			$version = $crawler->getLatestVersion($package);
		} elseif (!is_object($version)) {
			$version = $crawler->getVersion($package, $version);
		}
		if (!$path) {
			$path = getcwd();
		}

		$this->logger->info('---- downloading \'' . $version->getName() . 
			'\' of \'' . $package->getName() . 
			'\' from platform \'' . $crawler->getPlatformName() . '\'  ----');
		
		if ($crawler->downloadVersion($version, $path)) {
			$this->logger->info('---- finished downloading version ----');
			return true;
		} else {
			$this->logger->info('---- failed downloading version ----');
			return false;
		}
	}
	
	//*******************************************************************
	
	/**
	 * @return array (Package)
	 */
	public function selectCrawlPackages() {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->select('pkg')->where('pkg.crawled = FALSE')->orderBy('pkg.addedDate');
		return $qb->getQuery()->getResult();
	}
	
}
