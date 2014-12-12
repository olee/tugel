<?php

namespace Zeutzheim\PpintBundle\Model\Platform;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use Zeutzheim\PpintBundle\Exception\PackageNotFoundException;
use Zeutzheim\PpintBundle\Exception\VersionNotFoundException;
use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\AbstractPlatform;

use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;

use Zeutzheim\PpintBundle\Util\Utils;

function endsWith($haystack, $needle) {
	return strcasecmp(substr($haystack, strlen($haystack) - strlen($needle), strlen($needle)), $needle) == 0;
}
	
class PyPi extends AbstractPlatform {

	public function doDownload(Package $package, $path, $version) {
		if (empty($package->data['downloadurl']))
			return AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND;
		
		$fn = basename($package->data['downloadurl']);
		$url = Utils::rawurlEncode($package->data['downloadurl']);
		if (!$this->downloadFile($url, $path . $fn))
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		
		// Extract files
		$this->log('Extracting ' . $fn, $package, Logger::DEBUG);
		if (endsWith($fn, '.zip'))
			$cmd = 'unzip ' . escapeshellarg($path . $fn) . ' -d ' . escapeshellarg($path);
		else if (endsWith($fn, '.tar.bz2'))
			$cmd = 'tar -xjof ' . escapeshellarg($path . $fn) . ' --strip-components=1 -C ' . escapeshellarg($path) . ' && chmod -Rf 775 ' . escapeshellarg($path);
		else if (endsWith($fn, '.tar.gz'))
			$cmd = 'tar -xzof ' . escapeshellarg($path . $fn) . ' --strip-components=1 -C ' . escapeshellarg($path) . ' && chmod -Rf 775 ' . escapeshellarg($path);
		else
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		$cmd .= ' && rm ' . escapeshellarg($path . $fn);
		exec($cmd, $output, $success);
		if ($success !== 0)
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
	}

	//*******************************************************************

	public function getName() {
		return 'pypi';
	}

	public function getBaseUrl() {
		return 'https://pypi.python.org/pypi/';
	}

	public function getCrawlUrl() {
		return 'https://pypi.python.org/pypi?%3Aaction=rss';
	}

	public function getMasterVersion() {
		return null;
	}

	public function getPackageRegex() {
		return '@<link>http://pypi.python.org/pypi/([^/]+)/[^/<]+</link>@i';
	}

	public function getPackageData(Package $package) {
		$json = $this->httpGet($this->getBaseUrl() . $package->getName() . '/json');
		if ($json === false) {
			// echo 'Error downloading data'; exit;
			return false;
		}
		
		$data = json_decode($json, TRUE);
		if ($data === null) {
			// echo 'Error reading data'; exit;
			return false;
		}
		
		$downloadurl = null;
		$version = $data['info']['version'];
		if (empty($data['releases'][$version])) {
			$version = null;
		} else {
			foreach ($data['releases'][$version] as $release) {
				if ($release['packagetype'] == 'sdist') {
					$downloadurl = $release['url'];
					break;
				}
			}
		}
		
		// Get basic package information
		$package->data = array(
			//'description' => $data['info']['description'],
			'description' => $data['info']['summary'],
			'version' => $version,
			'packagename' => $data['info']['name'],
			'downloadurl' => $downloadurl,
		);
		return true;
	}

}
