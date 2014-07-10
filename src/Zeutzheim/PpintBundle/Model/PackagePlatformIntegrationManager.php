<?php

namespace Zeutzheim\PpintBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;

use Zeutzheim\PpintBundle\Model\Platform;
use Zeutzheim\PpintBundle\Model\PlatformManager;
use Zeutzheim\PpintBundle\Model\Language;
use Zeutzheim\PpintBundle\Model\LanguageManager;

use Zeutzheim\PpintBundle\Entity\Platform as PlatformEntity;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

class PackagePlatformIntegrationManager {

	/**
	 * @var EntityManagerInterface
	 */
	private $em;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var PlatformManager
	 */
	private $platformManager;

	/**
	 * @var LanguageManager
	 */
	private $languageManager;

	public function __construct(EntityManagerInterface $em, Logger $logger, PlatformManager $platformManager, LanguageManager $languageManager) {
		$this->em = $em;
		$this->logger = $logger;
		$this->platformManager = $platformManager;
		$this->languageManager = $languageManager;
	}

	//*******************************************************************

	public function crawlPlatforms() {
		$this->log('started crawling platforms', null, Logger::NOTICE);
		set_time_limit(60 * 60);
		foreach ($this->getPlatformManager()->getPlatforms() as $platform) {
			$platform->crawlPlatform();
		}
		$this->log('finished crawling platforms', null, Logger::NOTICE);
	}

	//*******************************************************************

	public function loadPackagesData($maxTime = 0) {
		$this->log('started crawling packages', null, Logger::NOTICE);

		// Load package list
		$packages = $this->selectCrawlPackages();

		// Get start time and set time limit
		if ($maxTime <= 0)
			$maxTime = 604800;
		$startTime = time();
		set_time_limit(60 * 5 + $maxTime);

		// Iterate over all packages that need to be scanned
		foreach ($packages as $package) {
			if (time() - $startTime > $maxTime) {
				$this->log('maximum execution time reached', null, Logger::NOTICE);
				break;
			}
			$this->getPlatformManager()->getPlatform($package->getPlatform()->getName())->loadPackageData($package);
		}

		$this->log('finished crawling packages', null, Logger::NOTICE);
	}

	//*******************************************************************

	public function download($platform, $package, $version = null, $path = null) {
		if (!is_object($platform)) {
			$platform = $this->getPlatformManager()->getPlatform($platform);
		}
		if (!is_object($package)) {
			$package = $platform->getPackage($package);
		}
		if (!$version) {
			$version = $platform->getLatestVersion($package);
		} elseif (!is_object($version)) {
			$version = $platform->getVersion($package, $version);
		}
		if (!$path) {
			$path = getcwd();
		}

		$this->getLogger()->info('> downloading \'' . $version->getName() . '\' of \'' . $package->getName() . '\' from platform \'' . $platform->getName() . '\' ');

		if ($platform->downloadVersion($version, $path)) {
			$this->getLogger()->info('> finished downloading version');
			return true;
		} else {
			$this->getLogger()->info('> failed downloading version');
			return false;
		}
	}

	//*******************************************************************

	public function findPackagesBySource($filename, $src = null) {
		if (!$src) {
			if (!$filename || !file_exists($filename))
				throw new \RuntimeException();
			$src = file_get_contents($filename);
		}
		
		$index = array();
		foreach ($this->getLanguageManager()->getLanguages() as $lang)
			if ($lang->checkFilename($filename)) {
				$index = array_merge_recursive($index, array($lang->getName() => $lang->analyzeUse($src)));
			}
		$index = $this->getLanguageManager()->collapseIndex($index);
		
		$finder = $this->container->get('fos_elastica.finder.search.version');
		$boolQuery = new \Elastica\Query\Bool();
		
		$fieldQuery = new \Elastica\Query\Match();
		$fieldQuery->setFieldQuery('namespaces', $index['namespace']);
		$fieldQuery->setFieldParam('namespaces', 'analyzer', 'identifier');
		$boolQuery->addShould($fieldQuery);
		
		$fieldQuery = new \Elastica\Query\Match();
		$fieldQuery->setFieldQuery('classes', $index['class']);
		$fieldQuery->setFieldParam('classes', 'analyzer', 'identifier');
		$boolQuery->addShould($fieldQuery);
		
		$fieldQuery = new \Elastica\Query\Match();
		$fieldQuery->setFieldQuery('languages', $index['language']);
		$fieldQuery->setFieldParam('languages', 'analyzer', 'whitespace');
		$boolQuery->addShould($fieldQuery);
		
		//print_r($index);
		//print_r($boolQuery->toArray());
		//print_r(json_encode($boolQuery->toArray()));
		echo json_encode(array("query" => $boolQuery->toArray())) . "\n";

		$data = $finder->find($boolQuery);
		
		foreach ($data as $value) {
			echo $value->getPackage()->getName() . "\n";
		}
		
		return array();
	}

	//*******************************************************************

	public function index($platform = null, $package = null, $version = null, $maxTime = 1800, $printCachesize = false) {
		if ($platform) {
			if (!is_object($platform))
				$platform = $this->getPlatformManager()->getPlatform($platform);

			if ($package) {
				if (!is_object($package))
					$package = $platform->getPackage($package);

				if (!$version) {
					$version = $platform->getLatestVersion($package);
				} elseif (!is_object($version)) {
					$version = $platform->getVersion($package, $version);
				}

				return $platform->indexVersion($version);
			}
		}

		if ($platform) {
			$this->log('indexing platform', $platform, Logger::NOTICE);
		} else {
			$this->log('indexing all platforms', null, Logger::NOTICE);
		}
		
		if ($printCachesize) {
			// Print size of cache directory
			exec('du -hs ' . escapeshellarg(WEB_DIRECTORY . '../tmp/'), $output);
			$size = preg_split('@\\s+@', implode(' ', $output));
			$this->log('cache directory size = ' . $size[0]);
		}
		$this->log('- - - - - - - - - - - - - - - - -');

		// Load package list
		$packages = $this->selectAnalyzePackages($platform ? $platform->getPlatformEntity() : null);
		$packageRepository = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package');

		// Get start time and set time limit
		$startTime = time();
		set_time_limit(60 * 10 + $maxTime);

		foreach ($packages as $package) {
			// Fetch package
			$this->getEntityManager()->clear('ZeutzheimPpintBundle:Package');
			$this->getEntityManager()->clear('ZeutzheimPpintBundle:Version');
			$package = $packageRepository->find($package);
			if (!$package)
				continue;

			$platform = $this->getPlatformManager()->getPlatform($package->getPlatform()->getName());
			$version = $platform->getLatestVersion($package);

			if ($version && !$version->isIndexed() && !$version->isError())
				$platform->indexVersion($version);
			else {
				$package->setIndexed(true);
				$this->log('version skipped ' . (!$version ? '(no version found)' : $version->isIndexed() ? '(already indexed' : '(has error)'), $version, Logger::NOTICE);
				$this->getEntityManager()->flush();
			}
			
			if (time() - $startTime > $maxTime) {
				$this->log('maximum execution time reached', null, Logger::NOTICE);
				break;
			}
		}
		$this->getEntityManager()->flush();

		if ($platform) {
			$this->log('finished indexing platform', $platform, Logger::NOTICE);
		} else {
			$this->log('finished indexing all platforms', null, Logger::NOTICE);
		}
		return true;
	}

	//*******************************************************************
	
	public function log($msg, $obj = null, $logLevel = Logger::INFO) {
		if ($obj) {
			if ($obj instanceof PlatformEntity)
				$msg = str_pad($obj, Platform::PLATFORM_STR_LEN) . $msg;
			elseif ($obj instanceof Package)
				$msg = str_pad($obj->getPlatform(), Platform::PLATFORM_STR_LEN) . str_pad($obj, Platform::PACKAGE_STR_LEN) . $msg;
			elseif ($obj instanceof Version)
				$msg = str_pad($obj->getPackage()->getPlatform(), Platform::PLATFORM_STR_LEN) . str_pad($obj->getPackage(), Platform::PACKAGE_STR_LEN) . str_pad($obj, Platform::VERSION_STR_LEN) . $msg;
			elseif (is_string($obj))
				$msg = $obj . ' ' . $msg;
		}
		$this->getLogger()->log($logLevel, $msg);
	}
	
	//*******************************************************************

	/**
	 * @return EntityManagerInterface
	 */
	public function getEntityManager() {
		return $this->em;
	}

	/**
	 * @return Logger
	 */
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * @return PlatformManager
	 */
	public function getPlatformManager() {
		return $this->platformManager;
	}

	/**
	 * @return LanguageManager
	 */
	public function getLanguageManager() {
		return $this->languageManager;
	}
	
	//*******************************************************************
	
	/**
	 * @return array (Package)
	 */
	private function selectCrawlPackages() {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->select('pkg')->where('pkg.crawled = FALSE')->orderBy('pkg.addedDate');
		return $qb->getQuery()->getResult();
	}

	//*******************************************************************

	/**
	 * @return array (Package)
	 */
	private function selectAnalyzePackages(PlatformEntity $platform = null) {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->select('pkg.id')->andWhere('pkg.crawled = true')->andWhere('pkg.indexed = false')->orderBy('pkg.addedDate');
		if ($platform)
			$qb->leftJoin('ZeutzheimPpintBundle:Platform', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'pkg.platform = p.id')->andWhere('p = ' . $platform->getId());
		//echo $qb->getQuery()->getSQL() . "\n";
		$result = $qb->getQuery()->getArrayResult();
		foreach ($result as &$value)
			$value = $value['id'];
		return $result;
	}

}
