<?php

namespace Zeutzheim\PpintBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Zeutzheim\PpintBundle\Exception\PackageNotFoundException;
use Zeutzheim\PpintBundle\Exception\VersionNotFoundException;
use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\Platform;

use Zeutzheim\PpintBundle\Entity\Platform as PlatformEntity;
use Zeutzheim\PpintBundle\Entity\Package;
use Zeutzheim\PpintBundle\Entity\Version;

class Packagist extends Platform {

	public function doDownloadVersion(Version $version, $path) {
		$versionName = $version->getName();
		$json = $this->httpGet($this->getBaseUrl() . $version->getPackage()->getName() . '.json');
		$data = json_decode($json, true);
		//print_r($data['package']['versions'][$version->getName()]);

		if (!isset($data['package']['versions'][$version->getName()]))
			throw new VersionNotFoundException($version);

		$url = $data['package']['versions'][$version->getName()]['source']['url'];

		if (preg_match('@github\\.com/([^\.]*)@', $url, $matches)) {
			$this->getLogger()->debug('> Checking github url at ' . 'https://api.github.com/repos/' . $matches[1]);
			$result = $this->httpGet('https://api.github.com/repos/' . $matches[1] . '?client_id=6e91ea3626b5a9ff12bf&client_secret=a182ddb9a7b008e3395f8e743e3944fcccb178e7');
			if (!$result)
				throw new PackageNotFoundException($version);
		}
		if (preg_match('@bitbucket\\.org/([^\.]*)@', $url, $matches)) {
			$this->getLogger()->debug('> Checking bitbucket url at ' . 'https://api.bitbucket.org/1.0/repositories/' . $matches[1]);
			$result = $this->httpGet('https://api.bitbucket.org/1.0/repositories/' . $matches[1]);
			if (!$result)
				throw new PackageNotFoundException($version);
		}

		$this->getLogger()->debug('> checking out git repository ' . $url);

		exec('git clone --no-checkout ' . escapeshellarg($url) . ' ' . escapeshellarg($path) . ' && cd ' . escapeshellarg($path) . 
			' && git reset -q --hard ' . $data['package']['versions'][$version->getName()]['source']['reference'], $output, $success);
		if ($success !== 0)
			throw new DownloadErrorException($version);
		
		return true;
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

	public function getPackageDataUrl(Package $package) {
		return $this->getBaseUrl() . $package->getName() . '.json';
	}
	
	public function getPackageDataDescription($data) {
		$data = json_decode($data, true);
		return $data['package']['description'];
	}
	
	public function getPackageDataVersions($data) {
		$data = json_decode($data, true);
		$data = array_keys($data['package']['versions']);
		return $data;
	}
	
	public function getPackageDataMasterVersion($data, &$masterVersion) {
		$data = json_decode($data, true);
		$data = $data['package']['versions']['dev-master'];
		if (array_key_exists('source', $data))
			return $data['source']['reference'];
		else
			return $data['dist']['reference'];
	}

}
