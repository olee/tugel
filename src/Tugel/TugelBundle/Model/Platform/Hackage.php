<?php

namespace Tugel\TugelBundle\Model\Platform;

use Doctrine\ORM\EntityManager;

use Tugel\TugelBundle\Exception\DownloadErrorException;

use Tugel\TugelBundle\Model\AbstractPlatform;

use Tugel\TugelBundle\Entity\Platform;
use Tugel\TugelBundle\Entity\Package;

class Hackage extends AbstractPlatform {

	protected function doDownload(Package $package, $path) {
		$fn = $package->getName() . '-' . $package->getVersion() . '.tar.gz';
		$url = 'http://hackage.haskell.org/package/' . $package->getName() . '-' . $package->getVersion() . '/' . $fn;

		if (!$this->downloadFile($url, $path . $fn))
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
		
		// Extract files
		$cmd = 'tar -xzof ' . escapeshellarg($this->preparePath($path) . $fn) . ' --strip-components=1 -C ' . escapeshellarg($this->preparePath($path)) . ' && chmod -Rf 775 ' . escapeshellarg($this->preparePath($path)) . ' && rm ' . escapeshellarg($this->preparePath($path) . $fn);
		exec($cmd, $output, $success);
		if ($success !== 0)
			return AbstractPlatform::ERR_DOWNLOAD_ERROR;
	}

	//*******************************************************************

	public function getName() {
		return 'hackage';
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

	public function getPackageUrl(Package $package) {
		return 'https://hackage.haskell.org/package/' . $package->getName();
	}

	protected function doGetPackageData(Package $package) {
		$src = $this->httpGet('https://hackage.haskell.org/package/' . $package->getName());
		if ($src === false) {
			return AbstractPlatform::ERR_PACKAGE_NOT_FOUND;
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
	}

}
