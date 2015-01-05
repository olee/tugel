<?php

namespace Zeutzheim\PpintBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Zeutzheim\PpintBundle\Exception\PackageNotFoundException;
use Zeutzheim\PpintBundle\Exception\VersionNotFoundException;
use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\AbstractPlatform;

use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;

class Maven extends AbstractPlatform {

	public function doDownload(Package $package, $path, $version) {
		$parts = preg_split('/:/', $package->getName());
		$url = 'http://search.maven.org/remotecontent?filepath=' . str_replace('.', '/', $parts[0]) . '/' . $parts[1] . '/' . $package->getVersion() . '/' . $parts[1] . '-' . $package->getVersion() . '-javadoc.jar';
		$fn = $parts[1] . '-javadoc.jar';
		if (!$this->downloadFile($url, $path . $fn))
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		
		exit;
		
		// http://search.maven.org/remotecontent?filepath=com/github/xuwei-k/msgpack4z-api/0.1.0/msgpack4z-api-0.1.0-javadoc.jar
		return AbstractPlatform::ERR_NEEDS_REINDEXING;
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

	public function getPackageData(Package $package) {
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
