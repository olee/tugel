<?php

namespace Tugel\TugelBundle\Model\Platform;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use Tugel\TugelBundle\Exception\PackageNotFoundException;
use Tugel\TugelBundle\Exception\VersionNotFoundException;
use Tugel\TugelBundle\Exception\DownloadErrorException;

use Tugel\TugelBundle\Model\AbstractPlatform;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

use Tugel\TugelBundle\Util\Utils;

function endsWith($haystack, $needle) {
	return strcasecmp(substr($haystack, strlen($haystack) - strlen($needle), strlen($needle)), $needle) == 0;
}

class PyPi extends AbstractPlatform {

	protected function doDownload(Package $package, $path) {
		if (empty($package->data['downloadurl']))
			return AbstractPlatform::ERR_DOWNLOAD_NOT_FOUND;
		
		$fn = basename($package->data['downloadurl']);
		$url = Utils::rawurlEncode($package->data['downloadurl']);
		if (!$this->downloadFile($url, $path . $fn))
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		
		// Extract files
		$this->log('Extracting ' . $fn, $package, Logger::DEBUG);
		if (!$this->extractArchive($path, $fn)) {
			@unlink($path . $fn);
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		}
		
		@unlink($path . $fn);
	}

	//*******************************************************************

	public function getName() {
		return 'pypi';
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

	public function getPackageUrl(Package $package) {
		return 'https://pypi.python.org/pypi/' . $package->getName();
	}

	protected function doGetPackageData(Package $package) {
		$json = $this->httpGet('https://pypi.python.org/pypi/' . $package->getName() . '/json');
		if ($json === false) {
			// echo 'Error downloading data'; exit;
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
		}
		
		$data = json_decode($json, TRUE);
		if ($data === null) {
			// echo 'Error reading data'; exit;
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
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
	}

}
