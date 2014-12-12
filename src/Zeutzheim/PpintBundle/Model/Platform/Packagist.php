<?php

namespace Zeutzheim\PpintBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Zeutzheim\PpintBundle\Exception\PackageNotFoundException;
use Zeutzheim\PpintBundle\Exception\VersionNotFoundException;
use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\AbstractPlatform;

use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;

class Packagist extends AbstractPlatform {

	public function doDownload(Package $package, $path, $version) {
		$json = $this->httpGet($this->getBaseUrl() . $package->getName() . '.json');
		$data = json_decode($json, true);
		//print_r($data['package']['versions'][$version]);

		if (!isset($data['package']['versions'][$version]))
			return AbstractPlatform::ERR_VERSION_NOT_FOUND;

		$url = $data['package']['versions'][$version]['source']['url'];

		if (preg_match('@github\\.com/([^\.]*)@', $url, $matches)) {
			$this->getLogger()->debug('> Checking github url at ' . 'https://api.github.com/repos/' . $matches[1]);
			$result = $this->httpGet('https://api.github.com/repos/' . $matches[1] . '?client_id=6e91ea3626b5a9ff12bf&client_secret=a182ddb9a7b008e3395f8e743e3944fcccb178e7');
			if (!$result)
				return AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND;
		}
		if (preg_match('@bitbucket\\.org/([^\.]*)@', $url, $matches)) {
			$this->getLogger()->debug('> Checking bitbucket url at ' . 'https://api.bitbucket.org/1.0/repositories/' . $matches[1]);
			$result = $this->httpGet('https://api.bitbucket.org/1.0/repositories/' . $matches[1]);
			if (!$result)
				return AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND;
		}

		$this->getLogger()->debug('> checking out git repository ' . $url);

		exec('git clone --no-checkout ' . escapeshellarg($url) . ' ' . escapeshellarg($path) . ' && cd ' . escapeshellarg($path) . 
			' && git reset -q --hard ' . $data['package']['versions'][$version]['source']['reference'], $output, $success);
		if ($success !== 0)
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
	}

	//*******************************************************************

	public function getName() {
		return 'packagist';
	}

	public function getBaseUrl() {
		return 'https://packagist.org/packages/';
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

	public function getPackageData(Package $package) {
		$json = $this->httpGet($this->getBaseUrl() . $package->getName() . '.json');
		if ($json === false) {
			// echo 'Error downloading data'; exit;
			return false;
		}
		
		$data = json_decode($json, true);
		if ($data === null) {
			// echo 'Error reading data'; exit;
			return false;
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
			'description' => $data['package']['description'],
			'version' => $lastestVersion,
		);
		
		if ($lastestVersion) {
			// Get master version reference
			$master = $data['package']['versions'][$lastestVersion];
			$package->data['version-ref'] = array_key_exists('source', $master) ? $master['source']['reference'] : $master['dist']['reference'];
		}
			
		return true;
	}
	
}
