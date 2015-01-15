<?php

namespace Tugel\TugelBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Tugel\TugelBundle\Exception\PackageNotFoundException;
use Tugel\TugelBundle\Exception\VersionNotFoundException;
use Tugel\TugelBundle\Exception\DownloadErrorException;

use Tugel\TugelBundle\Model\AbstractPlatform;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

class Maven extends AbstractPlatform {

	protected function doDownload(Package $package, $path) {
		$parts = preg_split('/:/', $package->getName());
		$url = 'http://search.maven.org/remotecontent?filepath=' . str_replace('.', '/', $parts[0]) . '/' . $parts[1] . '/' . $package->getVersion() . '/' . $parts[1] . '-' . $package->getVersion() . '.jar';
		$fn = $parts[1] . '.jar';
		if (!$this->downloadFile($url, $path . $fn)) {
			//echo "Download failed: $url\n"; //exit;
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		}
		
		if (!$this->extractZip($path, $fn, true)) {
			echo "Extraction failed\n"; //exit;
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		}
	}

	//*******************************************************************

	public function getName() {
		return 'maven';
	}

	public function getCrawlUrl() {
		return 'http://mvnrepository.com/feeds/rss2.0.xml';
	}

	public function getMasterVersion() {
		return 'dev-master';
	}

	public function getPackageRegex() {
		return '@<link>https://packagist.org/packages/([^<]*)</link>@i';
	}
	
	public function getPackageUrl(Package $package) {
		$parts = preg_split('/:/', $package->getName());
		if (count($parts) < 2)
			return 'http://search.maven.org';
		if (!$package->getVersion()) {
			return 'http://mvnrepository.com/artifact/' . $parts[0] . '/' . $parts[1];
		}
		return 'http://search.maven.org/#artifactdetails|' . $parts[0] . '|' . $parts[1] . '|' . $package->getVersion() . '|';
	}

	protected function doGetPackageData(Package $package) {
		$parts = preg_split('/:/', $package->getName());
		$url = 'http://search.maven.org/solrsearch/select?q=g:' . $parts[0] . '%20AND%20a:' . $parts[1] . '&wt=json';
		$json = $this->httpGet($url);
		if ($json === false) {
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
		}
		
		$data = json_decode($json);
		if (!$data || empty($data->response->docs)) {
			// echo 'Error reading data'; exit;
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
		}
		
		// Get basic package information
		$package->data = array(
			'version' => $data->response->docs[0]->latestVersion,
		);
	}
	
}
