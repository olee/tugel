<?php

namespace Zeutzheim\PpintBundle\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Monolog\Logger;

use Zeutzheim\PpintBundle\Exception\PackageNotFoundException;
use Zeutzheim\PpintBundle\Exception\VersionNotFoundException;
use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\LanguageManager;
use Zeutzheim\PpintBundle\Model\Language;

use Zeutzheim\PpintBundle\Entity\Platform as PlatformEntity;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;
use Zeutzheim\PpintBundle\Entity\CodeTag;

abstract class Platform {
	
	const PLATFORM_STR_LEN = 12;
	const PACKAGE_STR_LEN = 46;
	const VERSION_STR_LEN = 14;

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
	 * @var PlatformEntity
	 */
	protected $platformEntity;

	/**
	 * @var PlatformEntity
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
	 * @var EntityRepository
	 */
	protected $versionRepo;

	/**
	 * @var QueryBuilder
	 */
	protected $packageQb;

	/**
	 * @var QueryBuilder
	 */
	protected $versionQb;

	public function __construct(EntityManager $em, Logger $logger, LanguageManager $languageManager) {
		$this->em = $em;
		$this->logger = $logger;
		$this->languageManager = $languageManager;
		$this->platformRepo = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Platform');
		$this->packageRepo = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Package');
		$this->versionRepo = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:Version');
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
				$package->setUrl($packageUri);
				$package->setPlatform($this->getPlatformReference());
				$this->getEntityManager()->persist($package);

				$this->log('Package added', $package);
			} else {
				$package->setCrawled(false);
				$this->log('Package updated', $package);
			}
		}
		$this->getEntityManager()->flush();
		$this->getEntityManager()->clear('ZeutzheimPpintBundle:Package');
		$this->getLogger()->notice('> finished crawling platform ' . $this->getName());
	}

	public function loadPackageData(Package $package = null, $crawlLatestVersion = false) {
		// If no package is passed, pick one to crawl
		if (!$package) {
			$package = $this->selectCrawlPackage();
			if (!$package)
				return false;
		}

		// Clear UnitOfWork to speed up flushing of entities when too large
		if ($this->getManagedEntityCount() > 60)
			$this->getEntityManager()->clear('ZeutzheimPpintBundle:Version');

		// Load data
		$data = $this->httpGet($this->getPackageDataUrl($package));
		if (!$data)
			return false;
		
		// Get description
		$package->setDescription($this->getPackageDataDescription($data));

		// Find versions from data
		$versions = array_unique($this->getPackageDataVersions($data));
		foreach ($versions as $versionName) {
			$versionName = (string) $versionName;
			// Add version to package
			$version = $this->getVersion($package, $versionName);
			$newVersion = $version == null;
			if ($newVersion) {
				$version = new Version();
				$version->setName($versionName);
				$version->setPackage($package);
				$this->getEntityManager()->persist($version);

				$package->setIndexed(false);

				$this->log('Version added', $version);
				//sleep(1);
			}

			// Check if a master-version was found and fetch the master-version identifiert (hash, date, etc.)
			if ($version->getName() == $this->getMasterVersion() && $this->getPackageDataMasterVersion($data, $masterVersion)) {
				// Check if the master-version is still up to date
				if ($package->getMasterVersionTag() != $masterVersion) {
					$package->setMasterVersionTag($masterVersion);
					if (!$newVersion) {
						$version->setIndexed(false);
						$version->setNamespaces(null);
						$version->setClasses(null);
						$version->setAddedDate(new \DateTime());
						$package->setIndexed(false);

						$this->log('Version updated', $version);
					}
				}
			}
		}
		$package->setCrawled(true);

		// Flush changes
		$this->getEntityManager()->flush();

		if ($crawlLatestVersion) {
			$this->index($this->getLatestVersion($package));
		}

		return true;
	}

	public function index(Version $version, $useExistingSource = true) {
		//$this->log('indexing... (use-existing-source: ' . ($useExistingSource ? 'true' : 'false') . ')', $version, Logger::DEBUG);
		$this->log('indexing... (use-existing-source: ' . ($useExistingSource ? 'true' : 'false') . ')', $version, Logger::INFO);

		// Set path for package download
		$path = WEB_DIRECTORY . '../tmp/' . $version->getPackage()->getPlatform()->getName() . '/' . $version->getPackage()->getName() . '/' . $version->getName() . '/';

		// Delete existing files if invalid (missing "ppint_repository" file) or $useExistingSource is not set
		if (file_exists($path) && (!$useExistingSource || !file_exists($path . 'ppint_repository')))
			exec('rm -rf ' . escapeshellarg($path));

		// Check if package source needs to be downloaded
		if (!file_exists($path)) {
			// Create download directory
			mkdir($path, 0777, true);

			// Download package source
			//$this->log('downloading...', $version);
			if (!$this->downloadVersion($version, $path)) {
				return false;
			}
			if (file_exists($path . '.git/'))
				exec('rm -rf ' . escapeshellarg($path . '.git/'));

			// Create file to mark downloaded files as valid in case of errors
			file_put_contents($path . 'ppint_repository', '');
		} else {
			$this->log('using existing files', $version);
		}

		// List all files of the package
		//$this->log('scanning files', $version);
		$files = $this->recursiveScandir($path);

		// Index files
		$i = 0;
		$index = array();
		foreach ($files as $file) {
			foreach ($this->getLanguageManager()->getLanguages() as $lang) {
				if ($lang->checkFilename($file)) {
					$fileIndex = array($lang->getName() => $lang->analyzeProvide(file_get_contents($file)));
					PackagePlatformIntegrationManager::mergeIndex($index, $fileIndex);
				}
			}
		}
		// echo "\n\n"; print_r($index); exit;
		
		$index = PackagePlatformIntegrationManager::collapseIndex($index);
		// echo "\n\n"; print_r($index);
		// exit;

		$version->setClasses(array_get($index, 'class'));
		$version->setNamespaces(array_get($index, 'namespace'));
		$version->setLanguages(array_get($index, 'language'));
		foreach ((array_key_exists('tag', $index) ? $index['tag'] : array()) as $name => $count) {
			$tag = $this->getEntityManager()->getRepository('ZeutzheimPpintBundle:CodeTag')->find(array('version' => $version, 'name' => $name));
			if ($tag == null) {
				$this->getEntityManager()->persist(new CodeTag($version, $name, $count));
			} else {
				$tag->setCount($count);
			}
		}
		$version->setIndexed(true);
		$version->getPackage()->setIndexed(true);
		$this->getEntityManager()->flush();

		$this->log('indexed', $version);
		return true;
	}
	
	public function clearIndex(Version $version) {
		$version->setClasses(null);
		$version->setNamespaces(null);
		$version->setIndexed(null);
	}

	public function downloadVersion(Version $version, $path) {
		try {
			return $this->doDownloadVersion($version, $path);
		} catch (PackageNotFoundException $e) {
			$this->log('package not found', $version, Logger::ERROR);
			$version->getPackage()->setError(true);
			$version->setError(true);
			//$this->getEntityManager()->remove($version->getPackage());
			$this->getEntityManager()->flush();
		} catch (VersionNotFoundException $e) {
			$this->log('version not found', $version, Logger::ERROR);
			$version->setError(true);
			$this->getEntityManager()->remove($version);
			$this->getEntityManager()->flush();
		} catch (DownloadErrorException $e) {
			$this->log('download error', $version, Logger::ERROR);
			$version->setError(true);
			$this->getEntityManager()->flush();
		}
		return false;
	}

	//*******************************************************************

	public abstract function doDownloadVersion(Version $version, $path);

	//*******************************************************************

	public abstract function getName();

	public abstract function getBaseUrl();

	public abstract function getCrawlUrl();

	public abstract function getPackageRegex();

	public abstract function getMasterVersion();

	public abstract function getPackageDataUrl(Package $package);
	
	public abstract function getPackageDataDescription($data);
	
	public abstract function getPackageDataVersions($data);
	
	public abstract function getPackageDataMasterVersion($data, &$masterVersion);
	
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
	public function getPackage($url) {
		if (!$this->packageQb) {
			$this->packageQb = $this->packageRepo->createQueryBuilder('e');
			$this->packageQb->where('e.platform = ?1');
			$this->packageQb->andWhere('e.url = ?2');
		}
		return $this->packageQb->setParameters(array(
			1 => $this->getPlatformEntity()->getId(),
			2 => $url
		))->getQuery()->getOneOrNullResult();
	}

	/**
	 * @return Version
	 */
	public function getVersion(Package $package, $name) {
		if (!$this->versionQb) {
			$this->versionQb = $this->versionRepo->createQueryBuilder('v');
			$this->versionQb->where('v.package = ?0');
			$this->versionQb->andWhere('v.name = ?1');
		}
		return $this->versionQb->setParameters(array(
			$package->getId(),
			$name
		))->getQuery()->getOneOrNullResult();
	}

	/**
	 * @return Version
	 */
	public function getLatestVersion(Package $package) {
		$result = $this->versionRepo->createQueryBuilder('v')->where('v.package = ' . $package->getId())->andWhere('v.name = ?0')->setParameters(array($this->getMasterVersion()))->getQuery()->getOneOrNullResult();
		if (!$result)
			$result = $this->versionRepo->createQueryBuilder('v')->where('v.package = ' . $package->getId())->orderBy('v.addedDate')->setMaxResults(1)->getQuery()->getOneOrNullResult();
		return $result;
	}
	
	public function getIndexedVersion(Package $package) {
		$result = $this->versionRepo->createQueryBuilder('v')
			->where('v.package = ' . $package->getId())
			->andWhere('v.indexed = true')
			->setMaxResults(1)
			->getQuery()
			->getOneOrNullResult();
		return $result;
	}

	//*******************************************************************

	public function httpGet($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'github-olee');
		//curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		//curl_setopt($ch, CURLOPT_USERPWD, 'USERNAME:PASSWORD');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($statusCode != 200)
			return false;
		return $result;
	}

	public function downloadFile($url, $path) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FILE, fopen($path, 'w'));
		curl_setopt($ch, CURLOPT_URL, $url);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

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
	 * @return PlatformEntity
	 */
	public function getPlatformEntity() {
		if (!$this->platformEntity) {
			$this->platformEntity = $this->platformRepo->findOneByName($this->getName());
			if (!$this->platformEntity) {
				$this->platformEntity = new PlatformEntity();
				$this->platformEntity->setName($this->getName());
				$this->platformEntity->setBaseUrl($this->getBaseUrl());
				$this->getEntityManager()->persist($this->platformEntity);
				$this->getEntityManager()->flush();
			}
		}
		return $this->platformEntity;
	}

	/**
	 * @return PlatformEntity
	 */
	public function getPlatformReference() {
		if (!$this->platformReference) {
			$this->platformReference = $this->getEntityManager()->getReference('ZeutzheimPpintBundle:Platform', $this->getPlatformEntity()->getId());
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
