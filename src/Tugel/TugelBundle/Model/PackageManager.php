<?php

namespace Tugel\TugelBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\DBAL\Schema\View;
use Monolog\Logger;

use FOS\ElasticaBundle\Finder\FinderInterface;
use Elastica\Type;
use Elastica\Query;
use Elastica\Query\Filtered;
use Elastica\Query\Bool;
use Elastica\Query\Match;
use Elastica\Query\MultiMatch;
use Elastica\Filter\BoolAnd;
use Elastica\Filter\AbstractMulti;
use Elastica\Filter\Term as TermFilter;

use ssko\UtilityBundle\Core\ContainerAwareHelperNT;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;
use Tugel\TugelBundle\Util\Utils;

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
	private $tagRepository;

	/**
	 * @var Type
	 */
	private $packageIndex;

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

	public function __construct(EntityManagerInterface $em, Logger $logger, PlatformManager $platformManager, LanguageManager $languageManager, Type $packageIndex, FinderInterface $finder, $stopwatch) {
		$this->em = $em;
		$this->logger = $logger;
		$this->platformManager = $platformManager;
		$this->languageManager = $languageManager;
		$this->packageIndex = $packageIndex;
		$this->finder = $finder;
		$this->stopwatch = $stopwatch;
		
		$this->finderHelper = new TransformedFinderHelper($this->finder);
		$this->packageRepository = $this->getEntityManager()->getRepository('TugelBundle:Package');
		$this->tagRepository = $this->getEntityManager()->getRepository('TugelBundle:Tag');
		
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

	/*******************************************************************/

	public function crawlPlatforms() {
		$this->log('started crawling platforms', null, Logger::NOTICE);
		set_time_limit(60 * 60);
		foreach ($this->getPlatformManager()->all() as $platform) {
			$platform->crawlPlatform();
		}
		$this->log('finished crawling platforms', null, Logger::NOTICE);
	}

	public function index($indexPlatform = null, $package = null, $maxTime = 1800, $printCachesize = false, $quick = false, $dry = false) {
		if ($indexPlatform) {
			if (!is_object($indexPlatform)) {
				$platformName = $indexPlatform;
				$indexPlatform = $this->getPlatformManager()->get($platformName);
				if (!$indexPlatform) {
					$this->log(sprintf('platform %s not found not found', $platformName), Logger::ERROR);
					return false;
				}
			}

			if ($package) {
				if (!is_object($package)) {
					$packageName = $package;
					$package = $indexPlatform->getPackage($packageName);
				}
				if (!$package) {
					$this->log('package not found', $indexPlatform ? $indexPlatform->getPlatformEntity() : null, Logger::ERROR);
					//return false;
					
					$pkg = new Package();
					$pkg->setPlatform($indexPlatform->getPlatformReference());
					$pkg->setName($packageName);
					$this->getEntityManager()->persist($pkg);
					$this->getEntityManager()->flush();
					$package = $pkg;
				} else {
					$package->setError(AbstractPlatform::ERR_NEEDS_REINDEXING);
				}
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

	public function resetIndex($platform = null, $clear = false, $errors = false, $force = false) {
		$qb = $this->getEntityManager()->getRepository('TugelBundle:Package')->createQueryBuilder('pkg')->update();
		$qb->set('pkg.new', '1');
		if ($force)
			$qb->set('pkg.error', AbstractPlatform::ERR_NEEDS_REINDEXING);
		if ($clear)
			$qb->set('pkg.classes', null)->set('pkg.namespaces', null)->set('pkg.tagsText', null)->set('pkg.languages', null)->set('pkg.codeTagsMaximum', null);
		
		if ($platform) {
			if (!is_object($platform))
				$platform = $this->getPlatformManager()->get($platform);
			if (!$platform) {
				$this->log('platform not found', null, Logger::ERROR);
				return false;
			}
			$qb->andWhere('pkg.platform = ' . $platform->getPlatformReference()->getId());
		}
		
		if ($errors)
			$qb->andWhere('pkg.error IS NOT NULL');
		else
			$qb->andWhere('pkg.error IS NULL');
		
		$qb->getQuery()->execute();
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
			$platform = $this->getPlatformManager()->get($package->getPlatform()->getName());
			$platform->index($package, $quick, $dry);

			if ($cnt++ % 10 == 0) {
				$this->getEntityManager()->flush();
				$this->getEntityManager()->clear();
				// $this->packageRepository->clear();
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
			$platform = $this->getPlatformManager()->get($platform);
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
	
	public function getStats() {
		$platforms = array();
		
		$classesAgg = new \Elastica\Aggregation\Terms('combinedTags');
		$classesAgg->setField('combinedTags')->setSize(39);
		
		$licensesAgg = new \Elastica\Aggregation\Terms('licenses');
		$licensesAgg->setField('licenseNotAnalyzed')->setSize(39);

		$platformAgg = new \Elastica\Aggregation\Terms('platform');
		$platformAgg->setField('platform.id')->addAggregation($classesAgg)->addAggregation($licensesAgg);
		
		$query = Query::create(null)->addAggregation($classesAgg)->addAggregation($licensesAgg)->addAggregation($platformAgg);
		$aggregations = $this->packageIndex->search($query)->getAggregations();
		
		// Get global statistics
		$platformData = array();
		$platformData['stats'] = $aggregations;
		$platformData['name'] = 'Global statistics';
		$platformData['count'] = (int) $this->packageRepository->createQueryBuilder('pkg')->select('count(pkg)')->getQuery()->getSingleScalarResult();
		$platformData['indexed_count'] = (int) $this->packageRepository->createQueryBuilder('pkg')->select('count(pkg)')->andWhere('pkg.version IS NOT NULL')->andWhere('pkg.error IS NULL')->getQuery()->getSingleScalarResult();
		$platformData['error_count'] = (int) $this->packageRepository->createQueryBuilder('pkg')->select('count(pkg)')->andWhere('pkg.error IS NOT NULL')->getQuery()->getSingleScalarResult();
		$platformData['last_added'] = $this->packageRepository->createQueryBuilder('pkg')->orderBy('pkg.addedDate', 'DESC')->setMaxResults(10)->getQuery()->getResult();
		$platformData['last_indexed'] = $this->packageRepository->createQueryBuilder('pkg')->orderBy('pkg.indexedDate', 'DESC')->setMaxResults(10)->getQuery()->getResult();
		$platforms[0] = $platformData;
		
		// Get platform-specific statistics
		foreach ($this->getEntityManager()->getRepository('TugelBundle:Platform')->findBy(array(), array('name' => 'ASC')) as $platform) {
			$platformData = array();
			$platformData['name'] = $platform->getName();
			$platformData['count'] = (int) $this->packageRepository->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->getQuery()->getSingleScalarResult(); //
			$platformData['indexed_count'] = (int) $this->packageRepository->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.version IS NOT NULL')->andWhere('pkg.error IS NULL')->getQuery()->getSingleScalarResult();
			$platformData['error_count'] = (int) $this->packageRepository->createQueryBuilder('pkg')->select('count(pkg)')->where('pkg.platform = ' . $platform->getId())->andWhere('pkg.error IS NOT NULL')->getQuery()->getSingleScalarResult();
			$platformData['last_added'] = $this->packageRepository->createQueryBuilder('pkg')->where('pkg.platform = ' . $platform->getId())->orderBy('pkg.addedDate', 'DESC')->setMaxResults(10)->getQuery()->getResult();
			$platformData['last_indexed'] = $this->packageRepository->createQueryBuilder('pkg')->where('pkg.platform = ' . $platform->getId())->orderBy('pkg.indexedDate', 'DESC')->setMaxResults(10)->getQuery()->getResult();
			$platforms[$platform->getId()] = $platformData;
		}
		foreach ($aggregations['platform']['buckets'] as $stat) {
			$platforms[$stat['key']]['stats'] = $stat;
		}

		return array(
			'stats_' => print_r($aggregations, true),
			'platforms' => $platforms,
		);
	}

	/*******************************************************************/

	public function findPackagesBySource($filename, $src = null) {
		if (!$src) {
			if (!$filename || !file_exists($filename))
				throw new \RuntimeException();
			$src = file_get_contents($filename);
		}
		
		$index = array();
		foreach ($this->getLanguageManager()->all() as $lang) {
			if ($lang->checkFilename($filename)) {
				$fileIndex = array($lang->getName() => $lang->analyzeUse($src));
				PackageManager::mergeIndex($index, $fileIndex);
			}
		}
		$index = PackageManager::collapseIndex($index);
		
		return $this->findPackages(null, $index['namespace'], $index['class'], $index['language'], $index['tag']);
	}
	
	public function parseQuery($query) {
		$data = array(
			'raw' => $query,
		);
		$queryRegex = '/(.*)(?:\\s|^)%s:(?:(?:\'([^\']*)\')|([^\\s]+))\\s?(.*)/i';
		$types = array(
			'platform',
			'language',
			'license',
			'depends',
		);
		foreach ($types as $type) {
			if (preg_match(sprintf($queryRegex, $type), $query, $matches)) {
				$query = $matches[1] . $matches[4];
				if (isset($data[$type]))
					$data[$type] = $data[$type] . ' ' . $matches[2].$matches[3];
				else
					$data[$type] = $matches[2].$matches[3];
			}	
		}
		$data['query'] = $query;
		return $data;
	}

	public function find($query, $size = 20, $start = 0, $suggest = false) {
		if ($this->stopwatch)
			$this->stopwatch->start('package_search');
		
		if (is_string($query))
			$query = $this->parseQuery($query);
		
		if (is_array($query)) {
			$isEmpty = true;
			$q = new Bool();
			$filters = array();
			
			if (!empty($query['platform'])) {
				$platform = $query['platform'];
				if (is_string($platform)) {
					$platform = $this->getPlatformManager()->get($platformName = $platform);
					if ($platform)
						$platform = $platform->getPlatformReference()->getId();
					else
						$platform = $platformName;
				}
				if (!is_numeric($platform))
					$platform = 0;
				$term = new TermFilter();
				$term->setTerm('platform.id', is_object($platform) ? $platform->getId() : $platform);
				$filters[] = $term;
				$isEmpty = false;
			}
			
			if (!empty($query['language'])) {
				$match = new Match();
				$match->setFieldQuery('languages', $query['language']);
				$match->setFieldOperator('languages', 'and');
				$q->addMust($match);
				$isEmpty = false;
			}
			
			if (!empty($query['depends'])) {
				$match = new Match();
				$match->setFieldQuery('dependencies.name', $query['depends']);
				$match->setFieldOperator('dependencies.name', 'and');
				$q->addMust($match);
				$isEmpty = false;
			}
			
			if (!empty($query['namespace'])) {
				$match = new Match();
				$match->setFieldQuery('namespaces', $query['namespace']);
				$q->addShould($match);
				$isEmpty = false;
			}
			
			if (!empty($query['class'])) {
				$match = new Match();
				$match->setFieldQuery('classes', $query['class']);
				$q->addShould($match);
				$isEmpty = false;
			}
			
			if (!empty($query['license'])) {
				$match = new Match();
				$match->setFieldQuery('license', $query['license']);
				$q->addMust($match);
				$isEmpty = false;
			}
			
			if (!empty($query['query'])) {
				//$suggest = true;
				if ($suggest) {
					$match = new Match();
					$match->setFieldType('name', 'phrase_prefix');
					$match->setFieldParam('name', 'slop', 4);
					$match->setFieldParam('name', 'max_expansions', 100);
					$match->setFieldQuery('name', $query['query']);
					$match->setFieldBoost('name', 2);
					$q->addShould($match);
					
					$match = new Match();
					$match->setFieldType('description', 'phrase_prefix');
					$match->setFieldParam('description', 'slop', 20);
					$match->setFieldParam('description', 'max_expansions', 10);
					$match->setFieldQuery('description', $query['query']);
					$match->setFieldBoost('description', 0.0001);
					$q->addShould($match);
					
					/*
					$match = new Match();
					//$match->setFieldType('classes', 'phrase_prefix');
					//$match->setFieldParam('classes', 'slop', 10000);
					//$match->setFieldParam('classes', 'max_expansions', 20);
					$match->setFieldQuery('classes', $query['query']);
					$q->addShould($match);
					
					$match = new Match();
					$match->setFieldParam('classesAnalyzed', 'slop', 10000);
					$match->setFieldQuery('classesAnalyzed', $query['query']);
					$q->addShould($match);
					
					$match = new Match();
					$match->setFieldQuery('namespaces', $query['query']);
					$match->setFieldBoost('namespaces', 0.5);
					$q->addShould($match);
					
					$match = new Match();
					$match->setFieldQuery('namespacesAnalyzed', $query['query']);
					$match->setFieldBoost('namespacesAnalyzed', 0.5);
					$q->addShould($match);
					*/
				} else {
					/*
					$match = new Match();
					$match->setFieldQuery('classes', $query['query']);
					$terms = array('json');
					
					$script = str_replace("\t", '', str_replace("\n", ' ', str_replace("\r", '', PackageManager::SCRIPT)));
					$functionQuery = new ESQ\FunctionScore();
					$functionQuery->setScoreMode('sum');
					$functionQuery->setBoostMode('replace');
					$functionQuery->addFunction('script_score', array('script' => $script, 'params' => array('terms' => $terms)));
					$functionQuery->setQuery($match);
					$q->addMust($functionQuery);
					/**/
					
					/**/
					$match = new MultiMatch();
					$match->setType('most_fields');
					//$match->setType('best_fields'); $match->setTieBreaker(0.3);
					$match->setQuery($query['query']);
					$match->setFields(array(
						'name^0.2',
						'description^1',
						'classes^0.6',
						'classesAnalyzed^0.6',
						'namespaces^0.3',
						'namespacesAnalyzed^0.3',
					));
					$q->addShould($match);
					/**/
				}
				$isEmpty = false;
			}
			
			if (count($filters) > 0) {
				$query = new Filtered();
				$query->setQuery($q);
				if (count($filters) > 1) {
					$qf = new BoolAnd();
					$qf->setFilters($filters);
					$query->setFilter($qf);
				} else {
					$query->setFilter($filters[0]);
				}
			} else {
				$query = $q;
			}
		}

		/**
		 * @var Query
		 */
		$query = Query::create($query);
		$query->setSize($size);
		$query->setFrom($start);
		$query->setExplain(true);
		$this->lastQuery = $query->toArray();
		
		//echo "<pre>" . json_encode($this->lastQuery, JSON_PRETTY_PRINT); exit;
		
		if ($isEmpty) {
			$result = array();
			$this->lastResponse = null;
		} else {
			$result = $this->finderHelper->findWithScores($query, 1);
			$this->lastResponse = $this->finderHelper->lastResult->getResponse()->getData();
		}
		
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
	

	/*******************************************************************/
	
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

	/*******************************************************************/
	
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

	/*******************************************************************/

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
	
	/*******************************************************************/
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
				if ($type == 'tags') {
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
