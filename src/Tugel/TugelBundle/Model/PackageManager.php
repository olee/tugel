<?php

namespace Tugel\TugelBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Schema\View;
use Monolog\Logger;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;

use Tugel\TugelBundle\Util\Utils;

use Tugel\TugelBundle\Model\AbstractPlatform;
use Tugel\TugelBundle\Model\PlatformManager;
use Tugel\TugelBundle\Model\Language;
use Tugel\TugelBundle\Model\LanguageManager;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

use FOS\ElasticaBundle\Finder\FinderInterface;
use FOS\ElasticaBundle\Finder\TransformedFinder;

use Elastica\Query as ESQ;
use Elastica\Filter as ESF;

class PackageManager {
			
	const CODE_TAG_SCRIPT = <<<EOM
sum = 0;
for (tag in _source.codeTags) {
	for (term in terms) {
		if (tag.name == term) {
			sum += Math.sqrt(tag.count);
		}
	}
};
10 * sum / terms.size() + _source.codeTagsMaximum / codeTagsMaximum * 2;
EOM;

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
	 * @var EntityRepository
	 */
	private $packageRepository;
	
	/**
	 * @var EntityRepository
	 */
	private $codeTagRepository;

	/**
	 * @var FinderInterface
	 */
	private $finder;
	
	/**
	 * @var TransformedFinderHelper
	 */
	private $finderHelper;
	
	/**
	 * @var string
	 */
	public $lastQuery;
	
	/**
	 * @var integer
	 */
	public $lastQueryTime;
	
	/**
	 * @var Stopwatch
	 */
	public $stopwatch;

	public function __construct(EntityManagerInterface $em, Logger $logger, PlatformManager $platformManager, LanguageManager $languageManager, FinderInterface $finder, $stopwatch) {
		$this->em = $em;
		$this->logger = $logger;
		$this->platformManager = $platformManager;
		$this->languageManager = $languageManager;
		$this->finder = $finder;
		$this->stopwatch = $stopwatch;
		
		$this->finderHelper = new TransformedFinderHelper($this->finder);
		$this->packageRepository = $this->getEntityManager()->getRepository('TugelBundle:Package');
		$this->codeTagRepository = $this->getEntityManager()->getRepository('TugelBundle:CodeTag');
		
		$sm = $this->getEntityManager()->getConnection()->getSchemaManager();
		if (!$this->viewExists('new_packages')) {
			$view = new View('new_packages', $this->getNewPackagesQuery()->getQuery()->getSql());
			$sm->dropAndCreateView($view);
			$this->log('Created view ' . $view->getName(), null, Logger::NOTICE);
		}
		if (!$this->viewExists('updated_packages')) {
			$view = new View('updated_packages', $this->getUpdatedPackagesQuery()->getQuery()->getSql());
			$sm->dropAndCreateView($view);
			$this->log('Created view ' . $view->getName(), null, Logger::NOTICE);
		}
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

	public function index($indexPlatform = null, $package = null, $maxTime = 1800, $printCachesize = false, $quick = false, $dry = false) {
		if ($indexPlatform) {
			if (!is_object($indexPlatform))
				$indexPlatform = $this->getPlatformManager()->getPlatform($indexPlatform);

			if ($package) {
				if (!is_object($package))
					$package = $indexPlatform->getPackage($package);
				return $indexPlatform->index($package, $quick, $dry);
			}
		}

		if ($printCachesize) {
			$this->log('calculating cache size...');
			$this->log('cache directory size = ' . Utils::getDirectorySize(WEB_DIRECTORY . '../tmp/'), null, Logger::NOTICE);
		}

		// Get start time and set time limit
		$endTime = time() + $maxTime;
		set_time_limit(60 * 10 + $maxTime);

		// Load package list
		$this->log('- - - - - - - - - - - - - - - - -');
		$this->log('indexing new packages', $indexPlatform ? $indexPlatform->getPlatformEntity() : null, Logger::NOTICE);
		$packages = $this->selectNewPackages($indexPlatform ? $indexPlatform->getPlatformEntity() : null);
		$this->indexPackages($packages, $endTime, $quick, $dry);
		$this->log('finished indexing new packages', $indexPlatform ? $indexPlatform->getPlatformEntity() : null, Logger::NOTICE);
		
		if (time() >= $endTime)
			return true;
		
		$this->log('- - - - - - - - - - - - - - - - -');
		$this->log('indexing updated packages', $indexPlatform ? $indexPlatform->getPlatformEntity() : null, Logger::NOTICE);
		$packages = $this->getUpdatedPackages($indexPlatform ? $indexPlatform->getPlatformEntity() : null);
		$this->indexPackages($packages, $endTime, $quick, $dry);
		$this->log('finished indexing updated packages', $indexPlatform ? $indexPlatform->getPlatformEntity() : null, Logger::NOTICE);

		return true;
	}

	public function indexPackages(array $packages, $endTime = null, $quick = false, $dry = false) {
		$cnt = 0;
		foreach ($packages as $package) {
			// Fetch package
			if (!is_object($package))
				$package = $this->getPackage($package);
			if (!$package)
				continue;

			switch ($package->getError()) {
				case null:
				case AbstractPlatform::ERR_DOWNLOAD_ERROR:
				case AbstractPlatform::ERR_NEEDS_REINDEXING:
					break;
				case AbstractPlatform::ERR_PACKAGE_NOT_FOUND:
					$this->log('skipped - package not found', $package, Logger::NOTICE);
					continue 2;
				case AbstractPlatform::ERR_DOWNLOAD_ERROR:
					$this->log('skipped - download error', $package, Logger::NOTICE);
					continue 2;
				default:
					$this->log('skipped - unknown error', $package, Logger::NOTICE);
					continue 2;
			}

			// Start indexing
			$platform = $this->getPlatformManager()->getPlatform($package->getPlatform()->getName());
			$platform->index($package, $quick, $dry);

			if ($cnt++ % 10 == 0) {
				$this->getEntityManager()->flush();
				$this->getEntityManager()->clear();
				// $this->packageRepository->clear();
				// $this->codeTagRepository->clear();
			}
			
			if ($endTime && time() >= $endTime) {
				$this->log('maximum execution time reached', null, Logger::NOTICE);
				break;
			}
		}
		$this->getEntityManager()->flush();
	}

	public function download($platform, $package, $version = null, $path = null) {
		if (!is_object($platform)) {
			$platform = $this->getPlatformManager()->getPlatform($platform);
		}
		if (!is_object($package)) {
			$package = $platform->getPackage($package);
		}
		if (!$version) {
			$version = $package->getVersion();
		}
		if (!$path) {
			$path = getcwd();
		}

		$this->getLogger()->info('> downloading \'' . $version->getName() . '\' of \'' . $package->getName() . '\' from platform \'' . $platform->getName() . '\' ');

		if ($platform->download($version, $path, $version)) {
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
				PackageManager::mergeIndex($index, $fileIndex);
			}
		}
		$index = PackageManager::collapseIndex($index);
		
		return $this->findPackages(null, $index['namespace'], $index['class'], $index['language'], $index['tag']);
	}

	public function findPackages($platform = null, $namespaces = null, $classes = null, $languages = null, $codetags = null) {
		if ($this->stopwatch)
			$this->stopwatch->start('package_search');
		
		$query = new ESQ\Bool();
		
		if (!$namespaces && !$classes && !$languages && !$codetags)
			return array();
		
		if (is_string($platform)) {
			$platform = $this->getPlatformManager()->getPlatform($platform);
			if ($platform)
				$platform = $platform->getPlatformEntity();
		}
		if (!empty($platform)) {
			$term = new ESQ\Term();
			$term->setTerm('platform.id', is_object($platform) ? $platform->getId() : $platform);
			$query->addMust($term);
		}
		
		if (!empty($namespaces)) {
			$match = new ESQ\Match();
			$match->setFieldQuery('namespaces', $namespaces);
			$query->addShould($match);
		}
		
		if (!empty($classes)) {
			$match = new ESQ\Match();
			$match->setFieldQuery('classes', $classes);
			$query->addShould($match);
		}
		
		if (!empty($languages)) {
			$match = new ESQ\Match();
			$match->setFieldQuery('languages', strtolower($languages));
			$query->addMust($match);
		}
		
		if (!empty($codetags)) {
			$match = new ESQ\Match();
			$match->setFieldQuery('codeTagsText', $codetags);
			$query->addMust($match);
		}
		
		$query = \Elastica\Query::create($query);
		$query->setSize(25);
		$this->lastQuery = $query->toArray();
		
		$result = $this->getNormalizedScores($this->finderHelper->findWithScore($query), 2);
		if ($this->stopwatch)
			$this->lastQueryTime = $this->stopwatch->stop('package_search')->getDuration();
		return $result;
	}

	/**
	 * @return array
	 */
	public static function getNormalizedScores(array $results, $max = 0) {
		foreach ($results as &$value) {
			$max = max($value->_score, $max);
		}
		if ($max > 0) {
			foreach ($results as &$value) {
				$value->_percentScore = $value->_score / $max;
			}
		}
		return $results;
	}
	

	//*******************************************************************
	
	/**
	 * @return QueryBuilder
	 */
	private function getNewPackagesQuery(Platform $platform = null) {
		$qb = $this->packageRepository->createQueryBuilder('pkg')->select('pkg.id') //
			->andWhere('pkg.new = 1') //
			->andWhere('pkg.version IS NULL') //
			->andWhere('pkg.error IS NULL') //
			->addOrderBy('pkg.addedDate', 'ASC');
		if ($platform)
			$qb->leftJoin('TugelBundle:Platform', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'pkg.platform = p.id')->andWhere('p = ' . $platform->getId());
		return $qb;
	}
	
	/**
	 * @return array (integer)
	 */
	private function selectNewPackages(Platform $platform = null) {
		$result = $this->getNewPackagesQuery($platform)->getQuery()->getArrayResult();
		foreach ($result as &$value)
			$value = $value['id'];
		return $result;
	}

	/**
	 * @return QueryBuilder
	 */
	private function getUpdatedPackagesQuery(Platform $platform = null) {
		$qb = $this->packageRepository->createQueryBuilder('pkg')->select('pkg.id') //
			->andWhere('pkg.new = 1') //
			->andWhere('pkg.error IS NULL') //
			->orWhere('pkg.error = ' . AbstractPlatform::ERR_NEEDS_REINDEXING) //
			->addOrderBy('pkg.new', 'DESC') //
			->addOrderBy('pkg.indexedDate', 'ASC');
		if ($platform)
			$qb->leftJoin('TugelBundle:Platform', 'p', \Doctrine\ORM\Query\Expr\Join::WITH, 'pkg.platform = p.id')->andWhere('p = ' . $platform->getId());
		return $qb;
	}

	/**
	 * @return array (integer)
	 */
	private function getUpdatedPackages(Platform $platform = null) {
		$result = $this->getUpdatedPackagesQuery($platform)->getQuery()->getArrayResult();
		foreach ($result as &$value)
			$value = $value['id'];
		return $result;
	}

	//*******************************************************************
	
	public function log($msg, $obj = null, $logLevel = Logger::INFO) {
		if ($obj) {
			if ($obj instanceof Platform)
				$msg = str_pad($obj, AbstractPlatform::PLATFORM_STR_LEN) . ' ' . $msg;
			elseif ($obj instanceof Package)
				$msg = str_pad($obj->getPlatform(), AbstractPlatform::PLATFORM_STR_LEN) . ' ' . str_pad($obj, AbstractPlatform::PACKAGE_STR_LEN) . ' ' . str_pad($obj->getVersion(), AbstractPlatform::VERSION_STR_LEN) . ' ' . $msg;
			elseif (is_string($obj))
				$msg = $obj . ' ' . $msg;
		}
		$this->getLogger()->log($logLevel, $msg);
	}

	/**
	 * Checks, if a view exists
	 */
	private function viewExists($name) {
		return array_key_exists($name, $this->getEntityManager()->getConnection()->getSchemaManager()->listViews());
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
	
	/**
	 * @return Package
	 */
	public function getPackage($id) {
		return $this->packageRepository->find($id);
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

class TransformedFinderHelper extends TransformedFinder {
		
	protected $finder;
	
	public function __construct($finder)
	{
		$this->finder = $finder;
	}
	
	public function getSearchable($instance) {
		return $instance->searchable;
	}
	
	public function getTransformer($instance) {
		return $instance->transformer;
	}
	
	public function findWithScore($query, $limit = null) {
		$queryObject = \Elastica\Query::create($query);
		if (null !== $limit) {
			$queryObject->setSize($limit);
		}
		$this->lastQuery = $queryObject->toArray();
		
		$queryResults = $this->getSearchable($this->finder)->search($queryObject)->getResults();
		$results = $this->getTransformer($this->finder)->transform($queryResults);
		foreach ($results as $key => $entity) {
			$hit = $queryResults[$key]->getHit();
			$entity->_score = $hit['_score'];
		}
		return $results;
	}
	
}
