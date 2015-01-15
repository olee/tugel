<?php

namespace Tugel\TugelBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Monolog\Logger;

use Tugel\TugelBundle\Exception\PackageNotFoundException;
use Tugel\TugelBundle\Exception\VersionNotFoundException;
use Tugel\TugelBundle\Exception\DownloadErrorException;

use Tugel\TugelBundle\Model\LanguageManager;
use Tugel\TugelBundle\Model\Language;

use Tugel\TugelBundle\Entity\Platform as Platform;
use Tugel\TugelBundle\Entity\Package;
use Tugel\TugelBundle\Entity\CodeTag;

use Tugel\TugelBundle\Util\Utils;

abstract class AbstractPlatform {

	const PLATFORM_STR_LEN = 12;
	const PACKAGE_STR_LEN = 52;
	const VERSION_STR_LEN = 16;

	const ERR_PACKAGE_NOT_FOUND = 1;
	const ERR_VERSION_NOT_FOUND = 2;
	const ERR_DOWNLOAD_ERROR = 3;
	const ERR_OTHER_ERROR = 4;
	const ERR_DOWNLOAD_NOT_FOUND = 5;
	const ERR_NEEDS_REINDEXING = 10;
	const ERR_PACKAGEDATA_NOT_FOUND = 11;

	public $ERROR_MESSAGES = array(AbstractPlatform::ERR_PACKAGE_NOT_FOUND => 'Package not found', AbstractPlatform::ERR_VERSION_NOT_FOUND => 'Version not found', AbstractPlatform::ERR_DOWNLOAD_ERROR => 'Download failed', AbstractPlatform::ERR_OTHER_ERROR => 'Unknown error', AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND => 'Download not found', AbstractPlatform::ERR_NEEDS_REINDEXING => 'Needs reindexing', );

	/**
	 * @var EntityManagerInterface
	 */
	private $em;

	/**
	 * @var Logger
	 */
	private $logger;

	/**
	 * @var LanguageManager
	 */
	private $languageManager;

	/**
	 * @var Platform
	 */
	protected $platformEntity;

	/**
	 * @var Platform
	 */
	protected $platformReference;

	/**
	 * @var EntityRepository
	 */
	protected $platformRepo;

	/**
	 * @var EntityRepository
	 */
	protected $packageRepo;

	/**
	 * @var QueryBuilder
	 */
	protected $packageQb;

	/**
	 * constructor
	 */
	public function __construct(EntityManager $em, Logger $logger, LanguageManager $languageManager) {
		$this->em = $em;
		$this->logger = $logger;
		$this->languageManager = $languageManager;
		$this->platformRepo = $this->getEntityManager()->getRepository('TugelBundle:Platform');
		$this->packageRepo = $this->getEntityManager()->getRepository('TugelBundle:Package');
	}

	//*******************************************************************

	public function crawlPlatform() {
		$this->getLogger()->notice('> started crawling platform ' . $this->getName());

		$src = $this->httpGet($this->getCrawlUrl());
		if (!$src)
			return false;

		preg_match_all($this->getPackageRegex(), $src, $matches);
		$packages = array_unique($matches[1]);

		foreach ($packages as $packageUri) {
			$package = $this->getPackage($packageUri);
			if (!$package) {
				$package = new Package();
				$package->setName($packageUri);
				$package->setPlatform($this->getPlatformReference());
				$this->getEntityManager()->persist($package);

				$this->log('Package added', $package);
			} else {
				$package->setNew(true);
				$this->log('Package updated', $package);
			}
		}
		$this->getEntityManager()->flush();
		// $this->packageRepo->clear();
		$this->getLogger()->notice('> finished crawling platform ' . $this->getName());
	}

	public function index(Package $package, $quick = false, $dry = false) {
		if ($quick) {
			if (!$package->getVersion() || !file_exists($package->getCachePath() . 'tugel_repository'))
				$quick = false;
			else
				$package->data = array();
		}

		// Check package data
		if (!$quick) {
			if (!$this->getPackageData($package))
				return false;
			// Check, if version is the same as the last one indexed (master-versions)
			if (!$package->getError() && $package->getVersion() == $package->data['version']) {
				if (!array_key_exists('version-ref', $package->data) || $package->data['version-ref'] == $package->getVersionReference()) {
					$this->log('Same version', $package, Logger::INFO);
					if (!$dry)
						$package->setNew(0);
					return true;
				}
			}
			$package->setVersion($package->data['version']);
		}

		// Download package data
		$path = $package->getCachePath();
		$cacheVersion = $this->getCacheVersion($package, $path);
		if (!$quick && $cacheVersion != $package->getVersion()) {
			// Prepare download directory
			exec('rm -rf ' . escapeshellarg($path));
			mkdir($path, 0777, true);
			if (!$this->download($package, $path)) {
				if ($dry)
					$this->getEntityManager()->refresh($package);
				else
					$this->getEntityManager()->flush();
				return false;
			}
			// Create cache-file
			file_put_contents($path . 'tugel_repository', $package->getVersion());
		}
		
		// Index package
		$this->indexFiles($package, $path);
		if ($dry)
			$this->getEntityManager()->refresh($package);
		else
			$this->getEntityManager()->flush();
		$this->log('indexed', $package, Logger::DEBUG);
	}

	public function getCacheVersion(Package $package, $cachePath) {
		$cacheIdFile = $cachePath . 'tugel_repository';
		$cacheVersion = file_exists($cacheIdFile) ? file_get_contents($cacheIdFile) : false;
		if (array_key_exists('version-ref', $package->data) && $package->data['version-ref'] != $package->getVersionReference()) {
			$cacheVersion = false;
			$package->setVersionReference($package->data['version-ref']);
		}
		return $cacheVersion;
	}
	
	public function getPackageData(Package $package) {
		// Load package-data
		$this->log('Checking package data', $package, Logger::INFO);
		$err = $this->doGetPackageData($package);
		if ($err) {
			$package->setError($err);
			$this->log('Failed to get package data', $package, Logger::ERROR);
			return false;
		}

		// Get description
		if (array_key_exists('description', $package->data))
			$package->setDescription($package->data['description']);

		// Get package name if it's case-sensitive
		if (array_key_exists('packagename', $package->data))
			$package->setName($package->data['packagename']);

		// Check if a version was found
		if (!array_key_exists('version', $package->data) || !$package->data['version']) {
			$this->log('No version found', $package, Logger::WARNING);
			$package->setError(AbstractPlatform::ERR_VERSION_NOT_FOUND);
			return false;
		}
		return true;
	}
	
	public function download(Package $package, $path) {
		// Download package source
		$this->log('Downloading package', $package, Logger::INFO);
		$err = $this->doDownload($package, $path);
		if ($err) {
			$this->log('download error: ' . $this->ERROR_MESSAGES[$err], $package, Logger::ERROR);
			$package->setError($err);
			return false;
		}
		return true;
	}

	public function indexFiles(Package $package, $path) {
		// Index files
		$this->log('Indexing package', $package, Logger::INFO);
		$i = 0;
		$index = array();
		$files = $this->recursiveScandir($path);
		foreach ($files as $file) {
			foreach ($this->getLanguageManager()->getLanguages() as $lang) {
				if ($lang->checkFilename($file)) {
					$fn = substr($file, strlen($path), strlen($file) - strlen($path));
					$this->log('Indexing ' . $fn, $package, Logger::DEBUG);
					$fileIndex = array($lang->getName() => $lang->analyzeProvide($path, $fn));
					PackageManager::mergeIndex($index, $fileIndex);
				}
			}
		}
		$index = PackageManager::collapseIndex($index);

		$package->setClasses(array_get($index, 'class'));
		$package->setNamespaces(array_get($index, 'namespace'));
		$package->setLanguages(array_get($index, 'language'));
		$package->setCodeTagsText(array_get($index, 'tag2'));
		if (array_key_exists('tag', $index)) {
			$tags = $index['tag'];

			$max = 0;
			foreach ($tags as $count)
				$max = max($count, $max);
			foreach ($tags as &$count)
				$count /= $max;
			$package->setCodeTagsMaximum($max);

			foreach ($package->getCodeTags() as $tag) {
				if (array_key_exists($tag->getName(), $tags)) {
					$tag->setCount($tags[$tag->getName()]);
					unset($tags[$tag->getName()]);
				} else {
					$package->removeCodeTag($tag);
					// $this->getEntityManager()->remove($tag);
				}
			}
		}
		
		$package->setError(null);
		$package->setNew(false);
		$package->setIndexedDate(new \DateTime());
	}

	public function clearIndex(Package $package) {
		$package->setClasses(null);
		$package->setNamespaces(null);
		$package->setIndexed(null);
	}

	public function log($msg, $obj = null, $logLevel = Logger::INFO) {
		if ($obj) {
			if ($obj instanceof Platform)
				$msg = str_pad($obj->getName(), AbstractPlatform::PLATFORM_STR_LEN) . ' ' . $msg;
			elseif ($obj instanceof Package)
				$msg = str_pad($obj->getPlatform()->getName(), AbstractPlatform::PLATFORM_STR_LEN) . ' ' . str_pad($obj->getName(), AbstractPlatform::PACKAGE_STR_LEN) . ' ' . str_pad($obj->getVersion(), AbstractPlatform::VERSION_STR_LEN) . ' ' . $msg;
			elseif (is_string($obj))
				$msg = $obj . ' ' . $msg;
		}
		$this->getLogger()->log($logLevel, $msg);
	}

	//*******************************************************************

	public abstract function getName();

	public abstract function getCrawlUrl();

	public abstract function getPackageRegex();

	public abstract function getMasterVersion();

	protected abstract function doGetPackageData(Package $package);

	protected abstract function doDownload(Package $package, $path);

	public abstract function getPackageUrl(Package $package);

	//*******************************************************************

	/**
	 * @return array (integer)
	 */
	public function selectCrawlPackages() {
		$qb = $this->packageRepo->createQueryBuilder('pkg');
		$qb->select('pkg.id')->where('pkg.crawled = FALSE')->andWhere('pkg.platform = ' . $this->getPlatformEntity()->getId())->orderBy('pkg.addedDate');
		return $qb->getQuery()->getResult();
	}

	/**
	 * @return int
	 */
	public function getManagedEntityCount() {
		$count = 0;
		foreach ($this->getEntityManager()->getUnitOfWork()->getIdentityMap() as $entities)
			$count += count($entities);
		return $count;
	}

	/**
	 * @return Package
	 */
	public function getPackage($name) {
		if (!$this->packageQb) {
			$this->packageQb = $this->packageRepo->createQueryBuilder('e');
			$this->packageQb->where('e.platform = ?1');
			$this->packageQb->andWhere('e.name = ?2');
		}
		$res = $this->packageQb->setParameters(array(1 => $this->getPlatformReference(), 2 => $name))->getQuery()->getOneOrNullResult();
		//$this->log("search $name = " . $res, $this->getPlatformEntity());
		return $res;
	}

	//*******************************************************************

	public function httpGet($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'github-olee');
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		//curl_setopt($ch, CURLOPT_USERPWD, 'USERNAME:PASSWORD');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($statusCode != 200) {
			return false;
		}
		return $result;
	}

	public function httpCheck($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'github-olee');
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($statusCode != 200) {
			return false;
		}
		return true;
	}

	public function downloadFile($url, $path) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'github-olee');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FILE, fopen($path, 'w'));
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($statusCode != 200)
			return false;
		return $result;
	}

	public function strEndsWith($haystack, $needle) {
		return strcasecmp(substr($haystack, strlen($haystack) - strlen($needle), strlen($needle)), $needle) == 0;
	}

	public function preparePath($path) {
		if (PHP_OS == 'WINNT')
			$path = '/' . str_replace(':', '', str_replace('\\', '/', $path));
		return $path;
	}

	public function extractArchive($path, $fn, $deleteAfter = false) {
		if ($this->strEndsWith($fn, '.zip'))
			return $this->extractZip($path, $fn, $deleteAfter);
		else if ($this->strEndsWith($fn, '.tar.bz2'))
			return $this->extractTarBz2($path, $fn, $deleteAfter);
		else if ($this->strEndsWith($fn, '.tar.gz'))
			return $this->extractTarZip($path, $fn, $deleteAfter);
		else
			return false;
	}

	public function extractTarZip($path, $fn, $deleteAfter = false) {
		$cmd = 'tar -xzof ' . escapeshellarg($this->preparePath($path) . $fn) . ' --strip-components=1 -C ' . escapeshellarg($this->preparePath($path)) . ' && chmod -Rf 775 ' . escapeshellarg($this->preparePath($path));
		exec($cmd, $output, $success);
		if ($deleteAfter)
			$this->deleteFile($path, $fn);
		return $success == 0 || $success == 2;
	}

	public function extractTarBz2($path, $fn, $deleteAfter = false) {
		$cmd = 'tar -xjof ' . escapeshellarg($this->preparePath($path) . $fn) . ' --strip-components=1 -C ' . escapeshellarg($this->preparePath($path)) . ' && chmod -Rf 775 ' . escapeshellarg($this->preparePath($path));
		exec($cmd, $output, $success);
		if ($deleteAfter)
			$this->deleteFile($path, $fn);
		return $success == 0;
	}

	public function extractZip($path, $fn, $deleteAfter = false) {
		$cmd = 'unzip -o -d ' . escapeshellarg($path) . ' ' . escapeshellarg($path . $fn);
		exec($cmd, $output, $success);
		if ($deleteAfter)
			$this->deleteFile($path, $fn);
		return $success == 0;
	}

	public function deleteFile($path, $fn) {
		return @unlink($path . $fn);
	}

	public function checkRepository($url) {
		if (preg_match('@github\\.com/repos/([^/]+/[^/]+)@', $url, $matches)) {
			$this->getLogger()->debug('> Checking github url at ' . 'https://api.github.com/repos/' . $matches[1]);
			$result = $this->httpCheck('https://api.github.com/repos/' . $matches[1] . '?client_id=6e91ea3626b5a9ff12bf&client_secret=a182ddb9a7b008e3395f8e743e3944fcccb178e7');
			return $result;
		}
		if (preg_match('@github\\.com/([^/]+/[^/]+)@', $url, $matches)) {
			$this->getLogger()->debug('> Checking github url at ' . 'https://api.github.com/repos/' . $matches[1]);
			$result = $this->httpCheck('https://api.github.com/repos/' . $matches[1] . '?client_id=6e91ea3626b5a9ff12bf&client_secret=a182ddb9a7b008e3395f8e743e3944fcccb178e7');
			return $result;
		}
		if (preg_match('@bitbucket\\.org/([^/]+/[^/]+)@', $url, $matches)) {
			$this->getLogger()->debug('> Checking bitbucket url at ' . 'https://api.bitbucket.org/1.0/repositories/' . $matches[1]);
			$result = $this->httpCheck('https://api.bitbucket.org/1.0/repositories/' . $matches[1]);
			return $result;
		}
		return true;
	}

	public function recursiveScandir($path, $excludeHidden = true) {
		if ($path[strlen($path) - 1] != '/')
			$path .= '/';
		$files = array();
		foreach (scandir($path) as $file) {
			if ($excludeHidden && $file[0] == '.')
				continue;
			if ($file == '.' || $file == '..')
				continue;
			$fn = $path . $file;
			if (is_link($fn))
				continue;
			if (is_dir($fn)) {
				$files = array_merge($files, $this->recursiveScandir($fn . '/'));
			} else if (is_file($fn)) {
				$files[] = $fn;
			}
		}
		return $files;
	}

	//*******************************************************************

	/**
	 * @return EntityManager
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
	 * @return LanguageManager
	 */
	public function getLanguageManager() {
		return $this->languageManager;
	}

	/**
	 * @return Platform
	 */
	public function getPlatformEntity() {
		if (!$this->platformEntity) {
			$this->platformEntity = $this->platformRepo->findOneByName($this->getName());
			if (!$this->platformEntity) {
				$this->platformEntity = new Platform();
				$this->platformEntity->setName($this->getName());
				$this->getEntityManager()->persist($this->platformEntity);
				$this->getEntityManager()->flush();
			}
		}
		return $this->platformEntity;
	}

	/**
	 * @return Platform
	 */
	public function getPlatformReference() {
		if (!$this->platformReference || !$this->getEntityManager()->contains($this->platformReference)) {
			$this->platformReference = $this->getEntityManager()->getReference('TugelBundle:Platform', $this->getPlatformEntity()->getId());
		}
		return $this->platformReference;
	}

	//*******************************************************************
}

function array_get($array, $index) {
	if (array_key_exists($index, $array))
		return $array[$index];
	else
		return null;
}
