<?php

namespace Tugel\TugelBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Tugel\TugelBundle\Exception\PackageNotFoundException;
use Tugel\TugelBundle\Exception\VersionNotFoundException;
use Tugel\TugelBundle\Exception\DownloadErrorException;

use Tugel\TugelBundle\Model\AbstractPlatform;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

class Packagist extends AbstractPlatform {

	protected function doDownload(Package $package, $path) {
		if (isset($package->data[AbstractPlatform::PKG_GITHUB_ARCHIVE])) {
			$url = $package->data[AbstractPlatform::PKG_GITHUB_ARCHIVE];
			if (strpos($url, 'github.com')) {
				$url .= '?client_id=6e91ea3626b5a9ff12bf&client_secret=a182ddb9a7b008e3395f8e743e3944fcccb178e7';
			}
			if (!$this->checkRepository($url))
				return AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND;
			
			$fn = 'source.zip';
			if (!$this->downloadFile($url, $path . $fn))
				return AbstractPlatform::ERR_DOWNLOAD_ERROR;
			if (!$this->extractZip($path, $fn))
				return AbstractPlatform::ERR_DOWNLOAD_ERROR;
			@unlink($path . $fn);
			
		} else if (isset($package->data[AbstractPlatform::PKG_GIT])) {
			$url = $package->data[AbstractPlatform::PKG_GIT];
			if (!$this->checkRepository($url))
				return AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND;
	
			$this->getLogger()->debug('> checking out git repository ' . $url);
			exec('git clone --no-checkout ' . escapeshellarg($url) . ' ' . escapeshellarg($path) . ' && cd ' . escapeshellarg($path) . 
				' && git reset -q --hard ' . $package->data[AbstractPlatform::PKG_GIT_REF], $output, $success);
			// Delete .git cache
			if (file_exists($path . '.git/'))
				exec('rm -rf ' . escapeshellarg($path . '.git/'));
			if ($success !== 0)
				return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		} else 
			return AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND;
	}

	//*******************************************************************

	public function getName() {
		return 'packagist';
	}

	public function getCrawlUrl() {
		return 'https://packagist.org/feeds/releases.rss';
	}

	public function getMasterVersion() {
		return 'dev-master';
	}

	public function getPackageRegex() {
		return '@<link>https://packagist.org/packages/([^<]*)</link>@i';
	}

	public function getPackageUrl(Package $package) {
		return 'https://packagist.org/packages/' . $package->getName();
	}

	protected function doGetPackageData(Package $package) {
		$json = $this->httpGet('https://packagist.org/packages/' . $package->getName() . '.json');
		if ($json === false) {
			// echo 'Error downloading data'; exit;
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
		}
		
		$data = json_decode($json, true);
		if ($data === null) {
			// echo 'Error reading data'; exit;
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
		}
		
		$lastestVersion = null;
		$lastestVersionTime = 0;
		foreach ($data['package']['versions'] as $version => $versionData) {
			if ($versionData['time'] > $lastestVersionTime) {
				$lastestVersion = $version;
				$lastestVersionTime = $versionData['time'];
			}
		}

		if (array_key_exists('dev-master', $data['package']['versions'])) 
			$lastestVersion = 'dev-master';
		
		// Get basic package information
		$package->data = array(
			AbstractPlatform::PKG_DESCRIPTION => $data['package']['description'],
			AbstractPlatform::PKG_VERSION => $lastestVersion,
		);
		
		if ($lastestVersion) {
			// Get master version reference
			$versionData = $data['package']['versions'][$lastestVersion];
			$package->data[AbstractPlatform::PKG_VERSION_REF] = array_key_exists('source', $versionData) ? $versionData['source']['reference'] : $versionData['dist']['reference'];
			$package->data[AbstractPlatform::PKG_GITHUB_ARCHIVE] = array_key_exists('dist', $versionData) ? $versionData['dist']['url'] : null;
			$package->data[AbstractPlatform::PKG_GIT] = array_key_exists('source', $versionData) ? $versionData['source']['url'] : null;
			$package->data[AbstractPlatform::PKG_GIT_REF] = array_key_exists('source', $versionData) ? $versionData['source']['reference'] : null;
			if (isset($versionData['require']))
				$package->data[AbstractPlatform::PKG_DEPENDENCIES] = $versionData['require'];
			if (isset($versionData['license']))
				$package->data[AbstractPlatform::PKG_LICENSE] = join(', ', $versionData['license']);
		}
	}
	
}
