<?php

namespace Zeutzheim\PpintBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Zeutzheim\PpintBundle\Exception\DownloadErrorException;

use Zeutzheim\PpintBundle\Model\AbstractPlatform;

use Zeutzheim\PpintBundle\Entity\Platform;
use Zeutzheim\PpintBundle\Entity\Package;

class Hackage extends AbstractPlatform {

	public function doDownload(Package $package, $path, $version) {
		$fn = $package->getName() . '-' . $version . '.tar.gz';
		$url = 'http://hackage.haskell.org/package/' . $package->getName() . '-' . $version . '/' . $fn;

		if (!$this->downloadFile($url, $path . $fn))
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		
		// Extract files
		$cmd = 'tar -xzof ' . escapeshellarg($path . $fn) . ' --strip-components=1 -C ' . escapeshellarg($path) . ' && chmod -Rf 775 ' . escapeshellarg($path) . ' && rm ' . escapeshellarg($path . $fn);
		exec($cmd, $output, $success);
		if ($success !== 0)
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
	}

	//*******************************************************************

	public function getName() {
		return 'hackage';
	}

	public function getBaseUrl() {
		return 'https://hackage.haskell.org/package/';
	}

	public function getCrawlUrl() {
		return 'https://hackage.haskell.org/packages/recent.rss';
	}

	public function getMasterVersion() {
		return null;
	}

	public function getPackageRegex() {
		return '@<link>http://hackage.haskell.org/package/([^<]*)-[\d\.]*</link>@i';
	}

	public function getPackageData(Package $package) {
		$src = $this->httpGet($this->getBaseUrl() . $package->getName());
		if ($src === false) {
			return false;
		}
		
		$package->data = array();
		
		// Get versions
		preg_match_all('@href="/package/[^"]*-([\d\.]*\d)@i', $src, $matches);
		$package->data['version'] = end($matches[1]);
		
		// Get description
		preg_match('@id="content"[\s\S]*<div[\s\S]*</div>([\s\S]*)<hr@imx', $src, $matches);
		if (isset($matches[1])) {
			$package->data['description'] = strip_tags($matches[1]);
		}
		
		return true;
	}

}
