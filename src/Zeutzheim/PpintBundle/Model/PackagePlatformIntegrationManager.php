<?php

namespace Zeutzheim\PpintBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;

use Zeutzheim\PpintBundle\Util\Utils;

use Zeutzheim\PpintBundle\Model\Platform;
use Zeutzheim\PpintBundle\Model\PlatformManager;
use Zeutzheim\PpintBundle\Model\Language;
use Zeutzheim\PpintBundle\Model\LanguageManager;

use Zeutzheim\PpintBundle\Entity\Platform as PlatformEntity;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

use FOS\ElasticaBundle\Finder\FinderInterface;
use FOS\ElasticaBundle\Finder\TransformedFinder;

use Elastica\Query;
use Elastica\Query as ESQ;
use Elastica\Filter as ESF;
use Elastica\Query\Terms;
use Elastica\Query\Bool;
use Elastica\Query\FunctionScore;

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

	/**
	 * @var FinderInterface
	 */
	private $finder;
	
	private $transformedFinderAccessHelper;
	
	public $lastQuery;

	public function __construct(EntityManagerInterface $em, Logger $logger, PlatformManager $platformManager, LanguageManager $languageManager, FinderInterface $finder) {
		$this->em = $em;
		$this->logger = $logger;
		$this->platformManager = $platformManager;
		$this->languageManager = $languageManager;
		$this->finder = $finder;
		$this->transformedFinderAccessHelper = new TransformedFinderAccessHelper();
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
		foreach ($this->getLanguageManager()->getLanguages() as $lang) {
			if ($lang->checkFilename($filename)) {
				$fileIndex = array($lang->getName() => $lang->analyzeUse($src));
				PackagePlatformIntegrationManager::mergeIndex($index, $fileIndex);
			}
		}
		$index = PackagePlatformIntegrationManager::collapseIndex($index);
		
		return $this->findPackages($index['namespace'], $index['class'], $index['language']);
	}

	//*******************************************************************
	
	public function findPackages($namespaces = null, $classes = null, $languages = null, $codetags = null) {
		$query = new Bool();
		
		if (!$namespaces && !$classes && !$languages && !$codetags)
			return array();
		
		if ($namespaces) {
			$match = new ESQ\Match();
			$match->setFieldQuery('namespaces', $namespaces);
			$match->setFieldParam('namespaces', 'analyzer', 'identifier');
			$query->addShould($match);
		}
		
		if ($classes) {
			$match = new ESQ\Match();
			$match->setFieldQuery('classes', $classes);
			$match->setFieldParam('classes', 'analyzer', 'identifier');
			$query->addShould($match);
		}
		
		if ($languages) {
			$match = new ESQ\Match();
			$match->setFieldQuery('languages', strtolower($languages));
			$match->setFieldParam('languages', 'analyzer', 'whitespace');
			$query->addMust($match);
		}
		
		if ($codetags) {
			$terms = explode(' ', strtolower($codetags));
			
			$termsQuery = new ESF\Terms();
			$termsQuery->setTerms('codeTags.name', $terms);
			
			$script = <<<EOM
max = 1;
sum = 0;
for (tag in _source.codeTags) {
	if (tag.count > max) {
		max = tag.count;
	};
	for (term in terms) {
		if (tag.name == term) {
			sum += tag.count;
		}
	}
};
10 * sum / ((float) max + 1);
EOM;
			$script = str_replace("\t", '', str_replace("\n", ' ', str_replace("\r", '', $script)));
			
			// $script = 'query-codeTags';
			
			$functionQuery = new FunctionScore();
			$functionQuery->setScoreMode('sum');
			$functionQuery->setBoostMode('replace');
			// $functionQuery->addFunction('field_value_factor', array('field' => 'codeTags.count'));
			$functionQuery->addFunction('script_score', array('script' => $script, 'params' => array('terms' => $terms)));
			$functionQuery->setFilter($termsQuery);
			
			$query->addMust($functionQuery);
		}
		
		//print_r($index);
		// echo '<pre>'; print_r($query->toArray()); exit;
		//echo '<pre>'; print_r(json_encode($query->toArray())); exit;
		
		// return $this->findWithScore($query);
		
		$query = Query::create($query);
        $query->setSize(25);
		$this->lastQuery = $query->toArray();
		
		return $this->findWithScore($query);
		// return $this->finder->find($query);
	}

	public function findWithScore($query, $limit = null) {
		$queryObject = Query::create($query);
        if (null !== $limit) {
            $queryObject->setSize($limit);
        }
		$this->lastQuery = $queryObject->toArray();
        
		$queryResults = $this->transformedFinderAccessHelper->getSearchable($this->finder)->search($queryObject)->getResults();
        $results = $this->transformedFinderAccessHelper->getTransformer($this->finder)->transform($queryResults);
		foreach ($results as $key => $entity) {
			$hit = $queryResults[$key]->getHit();
			$entity->_score = $hit['_score'];
		}
        return $results;
	}

	//*******************************************************************

	public function index($indexPlatform = null, $package = null, $version = null, $maxTime = 1800, $printCachesize = false, $redownloadMaster = true) {
		if ($indexPlatform) {
			if (!is_object($indexPlatform))
				$indexPlatform = $this->getPlatformManager()->getPlatform($indexPlatform);

			if ($package) {
				if (!is_object($package))
					$package = $indexPlatform->getPackage($package);

				if (!$version) {
					$version = $indexPlatform->getLatestVersion($package);
				} elseif (!is_object($version)) {
					$version = $indexPlatform->getVersion($package, $version);
				}

				return $indexPlatform->index($version);
			}
		}

		if ($indexPlatform) {
			$this->log('indexing platform', $indexPlatform, Logger::NOTICE);
		} else {
			$this->log('indexing all platforms', null, Logger::NOTICE);
		}
		
		if ($printCachesize) {
			// Print size of cache directory
			$this->log('calculating cache size...');
			exec('du -hs ' . escapeshellarg(WEB_DIRECTORY . '../tmp/'), $output);
			$size = preg_split('@\\s+@', implode(' ', $output));
			$this->log('cache directory size = ' . $size[0], null, Logger::NOTICE);
		}
		$this->log('- - - - - - - - - - - - - - - - -');

		// Load package list
		$packages = $this->selectIndexPackages($indexPlatform ? $indexPlatform->getPlatformEntity() : null);
		$packageRepository = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package');

		// Get start time and set time limit
		$startTime = time();
		set_time_limit(60 * 10 + $maxTime);

		$cnt = 0;
		foreach ($packages as $package) {
			// Fetch package
			//$this->getEntityManager()->clear();
			//$this->getEntityManager()->clear('ZeutzheimPpintBundle:Package');
			//$this->getEntityManager()->clear('ZeutzheimPpintBundle:Version');
			$package = $packageRepository->find($package);
			if (!$package)
				continue;

			$platform = $this->getPlatformManager()->getPlatform($package->getPlatform()->getName());
			$version = $platform->getLatestVersion($package);

			if ($version && !$version->isIndexed() && !$version->isError()) {
				$platform->index($version, !($redownloadMaster && $version->getName() == $platform->getMasterVersion()));
			} else {
				$package->setIndexed(true);
				$this->log('skipped version ' . (
					!$version ? '(no version found)' : 
					$version->isIndexed() ? '(already indexed' : 
					$version->isError() ? '(has error)' :
					'(other error)'), $version, Logger::NOTICE);
			}

			if ($cnt++ > 20) {
				$cnt = 0;
				$this->getEntityManager()->flush();
				$this->getEntityManager()->clear();
				//$this->getEntityManager()->clear('ZeutzheimPpintBundle:Package');
				//$this->getEntityManager()->clear('ZeutzheimPpintBundle:Version');
				//$this->getEntityManager()->clear('ZeutzheimPpintBundle:CodeTag');
			}
			
			if (time() - $startTime > $maxTime) {
				$this->log('maximum execution time reached', null, Logger::NOTICE);
				break;
			}
		}
		$this->getEntityManager()->flush();

		if (time() - $startTime < $maxTime && false) {
			// No more packages to index first time - start re-indexing			
			if ($indexPlatform) {
				$this->log('reindexing platform', $indexPlatform, Logger::NOTICE);
			} else {
				$this->log('reindexing', null, Logger::NOTICE);
			}
			$packages = $this->selectReindexPackages($indexPlatform ? $indexPlatform->getPlatformEntity() : null);
			$this->log('- - - - - - - - - - - - - - - - -');
			$cnt = 0;
			foreach ($packages as $package) {
				// Fetch package
				$package = $packageRepository->find($package);
				if (!$package)
					continue;
	
				$platform = $this->getPlatformManager()->getPlatform($package->getPlatform()->getName());
				$indexedVersion = $platform->getIndexedVersion($package);
				$version = $platform->getLatestVersion($package);

				if ($cnt++ > 100) {
					$cnt = 0;
					$this->getEntityManager()->flush();
					$this->getEntityManager()->clear();
					//$this->getEntityManager()->clear('ZeutzheimPpintBundle:Package');
					//$this->getEntityManager()->clear('ZeutzheimPpintBundle:Version');
					//$this->getEntityManager()->clear('ZeutzheimPpintBundle:CodeTag');
				}
				
				if ($indexedVersion == $version) {
					$package->setIndexed(true);
					$this->log('version skipped (already indexed)', $version);
					continue;
				}
	
				if ($version && !$version->isIndexed() && !$version->isError() && $version->getName() != $platform->getMasterVersion()) {
					$platform->index($version, $version->getName() != $platform->getMasterVersion());
					$platform->clearIndex($indexedVersion);
				} else {
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
		}

		if ($indexPlatform) {
			$this->log('finished indexing platform', $indexPlatform, Logger::NOTICE);
		} else {
			$this->log('finished indexing all platforms', null, Logger::NOTICE);
		}
		return true;
	}

	//*******************************************************************
	
	public function log($msg, $obj = null, $logLevel = Logger::INFO) {
		if ($obj) {
			if ($obj instanceof PlatformEntity)
				$msg = str_pad($obj, Platform::PLATFORM_STR_LEN) . ' ' . $msg;
			elseif ($obj instanceof Package)
				$msg = str_pad($obj->getPlatform(), Platform::PLATFORM_STR_LEN) . ' ' . str_pad($obj, Platform::PACKAGE_STR_LEN) . ' ' . $msg;
			elseif ($obj instanceof Version)
				$msg = str_pad($obj->getPackage()->getPlatform(), Platform::PLATFORM_STR_LEN) . ' ' . str_pad($obj->getPackage(), Platform::PACKAGE_STR_LEN) . ' ' . str_pad($obj, Platform::VERSION_STR_LEN) . ' ' . $msg;
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
	private function selectIndexPackages(PlatformEntity $platform = null) {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->select('pkg.id')->andWhere('pkg.crawled = true')->andWhere('pkg.indexed = false')->orderBy('pkg.addedDate');
		if ($platform)
			$qb->leftJoin('ZeutzheimPpintBundle:Platform', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'pkg.platform = p.id')->andWhere('p = ' . $platform->getId());
		$result = $qb->getQuery()->getArrayResult();
		foreach ($result as &$value)
			$value = $value['id'];
		return $result;
	}

	/**
	 * @return array (Package)
	 */
	private function selectReindexPackages(PlatformEntity $platform = null) {
		$qb = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package')->createQueryBuilder('pkg');
		$qb->select('pkg.id')->andWhere('pkg.crawled = true')->orderBy('pkg.indexedDate');
		if ($platform)
			$qb->leftJoin('ZeutzheimPpintBundle:Platform', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'pkg.platform = p.id')->andWhere('p = ' . $platform->getId());
		$result = $qb->getQuery()->getArrayResult();
		foreach ($result as &$value)
			$value = $value['id'];
		return $result;
	}

	//*******************************************************************
	// Utility

	public static function mergeIndex(&$index, $index2)
	{
		foreach ($index2 as $lang => $types) {
			if (!array_key_exists($lang, $index))
				$index[$lang] = array();
			foreach ($types as $type => $identifiers) {
				if (!array_key_exists($type, $index[$lang]))
					$index[$lang][$type] = array();
				foreach ($identifiers as $ident => $count)
					Utils::array_add($index[$lang][$type], $ident, $count);
			}
		}
	}
	
	/**
	 * @return array
	 */
	public static function collapseIndex($index) {
		$result = array(
			'language' => strtolower(implode(' ', array_keys($index))),
		);
		foreach ($index as $lang => $types) {
			foreach ($types as $type => $identifiers) {
				if ($type == 'tag') {
					if (!array_key_exists($type, $result))
						$result[$type] = array();
					foreach ($identifiers as $identifier => $count) {
						Utils::array_add($result[$type], strtolower($identifier), $count);
					}
					continue;
				}
				$data = '';
				if ($type == 'namespace' || $type == 'class')
					$prefix = $lang . ':';
				else
					$prefix = '';
				foreach ($identifiers as $identifier => $count) {
					$data .= ' ' . $prefix . $identifier;
				}
				$result[$type] = trim($data);
			}
		}
		return $result;
	}
	
}

class TransformedFinderAccessHelper extends TransformedFinder {
    public function __construct()
    {
    }
	public function getSearchable($instance) {
		return $instance->searchable;
	}
	public function getTransformer($instance) {
		return $instance->transformer;
	}
}